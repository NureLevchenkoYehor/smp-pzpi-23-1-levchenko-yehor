-- Since products have a price, we will need to create a table for the currency.
CREATE TABLE IF NOT EXISTS [Currencies] (
    -- ISO 4217 currency code is standardized, so it is a acceptable.
    [currency_code] TEXT PRIMARY KEY,
    [currency_name] TEXT NOT NULL,
    [currency_symbol] TEXT NOT NULL
);

-- Currencies are static, so we can create a table with the default currencies.
INSERT INTO [Currencies] ([currency_code], [currency_name], [currency_symbol])
VALUES
    ('UAH', 'Ukrainian Hryvnia',      '₴'),
    ('USD', 'United States Dollar',   '$'),
    ('EUR', 'Euro',                   '€'),
    ('GBP', 'British Pound Sterling', '£'),
    ('JPY', 'Japanese Yen',           '¥'),
    ('AUD', 'Australian Dollar',      'A$'),
    ('CAD', 'Canadian Dollar',        'C$'),
    ('CHF', 'Swiss Franc',            'CHF'),
    ('CNY', 'Chinese Yuan Renminbi',  '¥'),
    ('SEK', 'Swedish Krona',          'kr'),
    ('NZD', 'New Zealand Dollar',     'NZ$'); 

-- Since products have an image, we will need to create a table for the images.
CREATE TABLE IF NOT EXISTS [Images] (
    -- Both path and name could be changed, so the id is better here.
    [image_id] INTEGER PRIMARY KEY AUTOINCREMENT,
    [image_url] TEXT NOT NULL,
    [image_name] TEXT NOT NULL,
    UNIQUE([image_url])
);

-- In the current system, images are static, so we have to create a table with the default images.
-- Image paths will point at the localserver with the port 8080, since we don't have a
-- specific server to store images.
INSERT INTO [Images] ([image_id], [image_url], [image_name])
VALUES
    (1, 'http://localhost:8080/assets/images/milk.png',    'milk.png'),
    (2, 'http://localhost:8080/assets/images/black.jpg',   'black.jpg'),
    (3, 'http://localhost:8080/assets/images/cheese.png',  'cheese.png'),
    (4, 'http://localhost:8080/assets/images/white.png',   'white.png'),
    (5, 'http://localhost:8080/assets/images/kefir.png',   'kefir.png'),
    (6, 'http://localhost:8080/assets/images/water.png',   'water.png'),
    (7, 'http://localhost:8080/assets/images/cookies.jpg', 'cookies.jpg');

CREATE TABLE IF NOT EXISTS [Products] (
    [product_id] INTEGER PRIMARY KEY AUTOINCREMENT,
    [product_name] TEXT NOT NULL,
    [product_price] REAL NOT NULL CHECK([product_price] >= 0),
    [currency_code] TEXT NOT NULL,
    [image_id] INTEGER,
    -- Products depends on currencies, since without currency we can't have a price.
    FOREIGN KEY ([currency_code]) REFERENCES Currencies([currency_code]) ON DELETE Cascade,
    FOREIGN KEY ([image_id]) REFERENCES Images([image_id]) ON DELETE SET NULL
);

-- In the current system, products are static, thus it is required to fill the table.
INSERT INTO [Products] ([product_id], [product_name], [product_price], [currency_code], [image_id])
VALUES
    (1, 'Молоко пастеризоване', 12, 'UAH', 1),
    (2, 'Хліб чорний',          9,  'UAH', 2),
    (3, 'Сир білий',            21, 'UAH', 3),
    (4, 'Сметана 20%',          25, 'UAH', 4),
    (5, 'Кефір 1%',             19, 'UAH', 5),
    (6, 'Вода газована',        18, 'UAH', 6),
    (7, 'Печиво "Весна"',       14, 'UAH', 7);

-- Registered user identities
CREATE TABLE IF NOT EXISTS [UserIdentities] (
    [user_identity_id] INTEGER PRIMARY KEY AUTOINCREMENT,
    [user_identity_email] TEXT NOT NULL UNIQUE,
    [user_identity_password] TEXT NOT NULL
);

-- Registered user profiles
CREATE TABLE IF NOT EXISTS [CustomerProfiles] (
    [customer_profile_id] INTEGER PRIMARY KEY AUTOINCREMENT,
    [customer_first_name] TEXT,
    [customer_last_name] TEXT,
    [customer_age] INTEGER CHECK([customer_age] >= 0)
);

-- Orders for both registered and unregistered users
CREATE TABLE IF NOT EXISTS [Orders] (
    [order_id] INTEGER PRIMARY KEY AUTOINCREMENT,
    [customer_id] INTEGER,
    [guest_id] TEXT,
    FOREIGN KEY ([customer_id]) REFERENCES UserIdentities([user_identity_id]) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS [OrderItems] (
    [order_item_id] INTEGER PRIMARY KEY AUTOINCREMENT,
    [product_id] INTEGER NOT NULL,
    [order_id] INTEGER NOT NULL,
    [order_item_quantity] INTEGER NOT NULL DEFAULT 1 CHECK([order_item_quantity] > 0),
    UNIQUE([product_id], [order_id]),
    -- Item shouldn't exist without an order.
    FOREIGN KEY ([product_id]) REFERENCES [Products]([product_id]) ON DELETE Cascade,
    FOREIGN KEY ([order_id]) REFERENCES [Orders]([order_id]) ON DELETE Cascade
);