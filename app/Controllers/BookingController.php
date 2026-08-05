<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Services\AvailabilityService;
use App\Services\MeetingService;
use App\Services\NotificationService;
use App\Models\User;

final class BookingController extends Controller
{
    /** Public booking page for a rep (by slug). */
    public function show(Request $request, array $params): never
    {
        $rep = (new User())->findBySlug((string) ($params['slug'] ?? ''));
        if ($rep === null || $rep['status'] !== 'active') {
            http_response_code(404);
            echo View::render('errors/404', ['title' => 'Niet gevonden'], 'layouts/blank');
            exit;
        }
        $slots = (new AvailabilityService((int) $rep['id']))->slots(14, 30);
        $this->view('booking/show', [
            'title' => 'Afspraak met ' . $rep['name'],
            'rep'   => $rep,
            'slots' => $slots,
        ], 'layouts/blank');
    }

    public function store(Request $request, array $params): never
    {
        $rep = (new User())->findBySlug((string) ($params['slug'] ?? ''));
        if ($rep === null) {
            $this->json(['error' => 'Niet gevonden'], 404);
        }
        if (!RateLimiter::attempt('booking:' . $request->ip(), 8, 3600)) {
            $this->json(['error' => 'Te veel aanvragen. Probeer later opnieuw.'], 429);
        }

        $this->validate($request, [
            'guest_name'  => 'required|max:160',
            'guest_email' => 'required|email',
            'slot'        => 'required',
        ], ['guest_name' => 'Naam', 'guest_email' => 'E-mail']);

        $slot = (string) $request->input('slot'); // "Y-m-d H:i"
        $startsAt = date('Y-m-d H:i:s', strtotime($slot));
        $endsAt = date('Y-m-d H:i:s', strtotime($slot) + 1800);

        $avail = new AvailabilityService((int) $rep['id']);
        if (!$avail->isFree($startsAt, $endsAt)) {
            $this->json(['error' => 'Dit tijdslot is net bezet. Kies een ander moment.'], 409);
        }

        $token = bin2hex(random_bytes(20));
        $id = Database::instance()->insert('bookings', [
            'user_id'       => (int) $rep['id'],
            'guest_name'    => (string) $request->input('guest_name'),
            'guest_email'   => strtolower((string) $request->input('guest_email')),
            'guest_phone'   => $request->input('guest_phone'),
            'guest_company' => $request->input('guest_company'),
            'subject'       => $request->input('subject') ?: 'Kennismaking',
            'message'       => $request->input('message'),
            'starts_at'     => $startsAt,
            'ends_at'       => $endsAt,
            'status'        => 'pending',
            'confirm_token' => $token,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        NotificationService::notify((int) $rep['id'], 'booking', 'Nieuwe afspraakaanvraag', $request->input('guest_name') . ' vraagt ' . date('d/m H:i', strtotime($slot)), '/bookings', 'calendar');

        $html = View::renderPartial('emails/booking_received', [
            'name' => (string) $request->input('guest_name'),
            'rep'  => $rep['name'],
            'when' => date('d/m/Y H:i', strtotime($slot)),
        ]);
        Mailer::send((string) $request->input('guest_email'), 'Je afspraakaanvraag is ontvangen', $html);

        $this->json(['ok' => true, 'redirect' => '/book/' . $rep['booking_slug'] . '/confirmed']);
    }

    public function confirmed(Request $request, array $params): never
    {
        $rep = (new User())->findBySlug((string) ($params['slug'] ?? ''));
        $this->view('booking/confirmed', ['title' => 'Aanvraag ontvangen', 'rep' => $rep], 'layouts/blank');
    }

    /** Admin: list booking requests. */
    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('bookings.manage', $request);
        $mine = Auth::isAdmin() ? '' : ' AND b.user_id = ' . (int) Auth::id();
        $bookings = Database::instance()->all(
            "SELECT b.*, u.name AS rep_name FROM bookings b JOIN users u ON u.id = b.user_id
             WHERE 1 {$mine} ORDER BY FIELD(b.status,'pending','approved','rejected','cancelled'), b.starts_at ASC LIMIT 200"
        );
        $this->view('booking/index', ['title' => 'Boekingen', 'bookings' => $bookings]);
    }

    public function approve(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('bookings.manage', $request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $db = Database::instance();
        $booking = $db->first('SELECT * FROM bookings WHERE id = ?', [$id]);
        if ($booking === null || $booking['status'] !== 'pending') {
            $this->json(['error' => 'Niet mogelijk.'], 422);
        }

        // Create the agenda event + free video meeting.
        $meeting = MeetingService::create((int) $booking['user_id'], (string) $booking['subject'], $booking['starts_at'], $booking['ends_at']);
        $eventId = $db->insert('agenda_events', [
            'user_id'    => (int) $booking['user_id'],
            'title'      => $booking['subject'] . ' — ' . $booking['guest_name'],
            'description'=> $booking['message'],
            'type'       => 'meeting',
            'starts_at'  => $booking['starts_at'],
            'ends_at'    => $booking['ends_at'],
            'teams_join_url' => $meeting['url'],
            'status'     => 'confirmed',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->update('bookings', ['status' => 'approved', 'event_id' => $eventId, 'teams_join_url' => $meeting['url'], 'updated_at' => date('Y-m-d H:i:s')], ['id' => $id]);

        $html = View::renderPartial('emails/booking_approved', [
            'name' => $booking['guest_name'],
            'when' => date('d/m/Y H:i', strtotime($booking['starts_at'])),
            'link' => $meeting['url'],
        ]);
        Mailer::send($booking['guest_email'], 'Je afspraak is bevestigd', $html);
        AuditLog::record('booking.approved', 'booking', $id);

        $this->json(['ok' => true]);
    }

    public function reject(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('bookings.manage', $request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $db = Database::instance();
        $booking = $db->first('SELECT * FROM bookings WHERE id = ?', [$id]);
        if ($booking !== null) {
            $db->update('bookings', ['status' => 'rejected', 'updated_at' => date('Y-m-d H:i:s')], ['id' => $id]);
            Mailer::send($booking['guest_email'], 'Over je afspraakaanvraag', View::renderPartial('emails/booking_rejected', ['name' => $booking['guest_name']]));
            AuditLog::record('booking.rejected', 'booking', $id);
        }
        $this->json(['ok' => true]);
    }
}
