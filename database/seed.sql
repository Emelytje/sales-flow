-- =============================================================================
--  SalesFlow Enterprise — Reference data seed
--  Permissions, role mappings, default sectors and application settings.
--  Contains NO demo/customer data. The admin account is created by the
--  installation wizard.
-- =============================================================================

SET NAMES utf8mb4;

-- ---- Permissions -----------------------------------------------------------
INSERT INTO permissions (slug, label, module) VALUES
    ('customers.view',    'Klanten bekijken',        'customers'),
    ('customers.create',  'Klanten aanmaken',        'customers'),
    ('customers.edit',    'Klanten bewerken',        'customers'),
    ('customers.delete',  'Klanten verwijderen',     'customers'),
    ('contacts.manage',   'Contacten beheren',       'contacts'),
    ('callboard.use',     'Callboard gebruiken',     'callboard'),
    ('agenda.view',       'Agenda bekijken',         'agenda'),
    ('agenda.manage',     'Agenda beheren',          'agenda'),
    ('bookings.manage',   'Boekingen beheren',       'bookings'),
    ('quotations.view',   'Offertes bekijken',       'quotations'),
    ('quotations.create', 'Offertes aanmaken',       'quotations'),
    ('quotations.edit',   'Offertes bewerken',       'quotations'),
    ('quotations.delete', 'Offertes verwijderen',    'quotations'),
    ('projects.manage',   'Projecten beheren',       'projects'),
    ('email.send',        'E-mails versturen',       'email'),
    ('map.view',          'Kaart bekijken',          'map'),
    ('reports.view',      'Rapporten bekijken',      'reports'),
    ('users.manage',      'Gebruikers beheren',      'settings'),
    ('settings.manage',   'Instellingen beheren',    'settings');

-- ---- Manager role: everything except user & system settings ---------------
INSERT INTO role_permissions (role, permission_id)
SELECT 'manager', id FROM permissions
WHERE slug IN (
    'customers.view','customers.create','customers.edit','customers.delete',
    'contacts.manage','callboard.use','agenda.view','agenda.manage',
    'bookings.manage','quotations.view','quotations.create','quotations.edit',
    'quotations.delete','projects.manage','email.send','map.view','reports.view'
);

-- ---- Sales role: day-to-day selling, no deletes/admin ----------------------
INSERT INTO role_permissions (role, permission_id)
SELECT 'sales', id FROM permissions
WHERE slug IN (
    'customers.view','customers.create','customers.edit',
    'contacts.manage','callboard.use','agenda.view','agenda.manage',
    'quotations.view','quotations.create','quotations.edit',
    'email.send','map.view'
);

-- ---- Default sectors -------------------------------------------------------
INSERT INTO sectors (name, created_at) VALUES
    ('Bouw & Constructie', NOW()),
    ('Horeca', NOW()),
    ('Retail', NOW()),
    ('IT & Software', NOW()),
    ('Gezondheidszorg', NOW()),
    ('Transport & Logistiek', NOW()),
    ('Vastgoed', NOW()),
    ('Productie', NOW()),
    ('Consultancy', NOW()),
    ('Financiële diensten', NOW());

-- ---- Default labels --------------------------------------------------------
INSERT INTO labels (name, color, created_at) VALUES
    ('Hot lead',      '#B33B62', NOW()),
    ('Terugbellen',   '#E98CAB', NOW()),
    ('VIP',           '#C99A2E', NOW()),
    ('Nieuwsbrief',   '#4C9AA6', NOW()),
    ('Beurs',         '#7A6FF0', NOW());

-- ---- Application settings --------------------------------------------------
INSERT INTO settings (setting_key, value, updated_at) VALUES
    ('company_name',        'SalesFlow Enterprise', NOW()),
    ('company_email',       '',                     NOW()),
    ('company_phone',       '',                     NOW()),
    ('company_address',     '',                     NOW()),
    ('company_vat',         '',                     NOW()),
    ('company_iban',        '',                     NOW()),
    ('currency',            'EUR',                  NOW()),
    ('default_tax_rate',    '21',                   NOW()),
    ('quotation_prefix',    'OFF-',                 NOW()),
    ('quotation_next',      '1',                    NOW()),
    ('quotation_valid_days','30',                   NOW()),
    ('primary_color',       '#E98CAB',              NOW()),
    ('accent_color',        '#B33B62',              NOW());
