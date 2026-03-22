-- ============================================
-- Database: CafeManagementAR (without delivery)
-- Purpose: Cafe Management AR System
-- Author: Akshan
-- Created: 2026-02-21
-- ============================================

-- 2. Create Database
CREATE DATABASE CafeManagementAR;
GO

-- 3. Use the Database
USE CafeManagementAR;
GO


-- ============================================
-- 4. Table: Users
-- Stores both Admins and Customers
-- ============================================
CREATE TABLE Users (
    UserID INT IDENTITY(1,1) PRIMARY KEY,
    FullName NVARCHAR(100) NOT NULL,
    ContactNumber NVARCHAR(20),
    Address NVARCHAR(200),
    Username NVARCHAR(50) UNIQUE NOT NULL,
    PasswordHash NVARCHAR(255) NOT NULL,
    Role NVARCHAR(20) CHECK(Role IN ('Admin', 'Customer')) NOT NULL,
    Email NVARCHAR(150) UNIQUE,
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE()
);
GO

ALTER TABLE Users
ADD Latitude DECIMAL(10,6) NULL,
    Longitude DECIMAL(10,6) NULL;

ALTER TABLE Users
ADD ProfileImage VARCHAR(255);

-- ============================================
-- 5. Table: MenuItems
-- Stores cafe menu items with AR model references
-- ============================================
CREATE TABLE MenuItems (
    MenuItemID INT IDENTITY(1,1) PRIMARY KEY,
    FoodName NVARCHAR(100) NOT NULL,
    Description NVARCHAR(500),
    Price DECIMAL(10,2) NOT NULL,
    Category NVARCHAR(50),
    PopularityScore INT DEFAULT 0,
    ImagePath NVARCHAR(255),
    Ingredients NVARCHAR(500),
    PortionSize NVARCHAR(50),
    NutritionalInfo NVARCHAR(500),
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE()
);
GO

ALTER TABLE MenuItems
ADD ARModelGLB NVARCHAR(255) NULL,
    ARModelUSDZ NVARCHAR(255) NULL;
GO

-- ============================================
-- 6. Table: Reservations
-- Stores customer table reservations (including walk-ins)
-- ============================================
CREATE TABLE Reservations (
    ReservationID INT IDENTITY(1,1) PRIMARY KEY,
    UserID INT NULL, -- NULL for walk-ins
    FullName NVARCHAR(100) NULL,
    Email NVARCHAR(150) NULL,
    SeatNumber NVARCHAR(10) NOT NULL,
    ReservationDate DATETIME NOT NULL,
    Status NVARCHAR(20) CHECK(Status IN ('Pending', 'Accepted', 'Declined')) DEFAULT 'Pending',
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    CONSTRAINT FK_Reservations_Users FOREIGN KEY(UserID) REFERENCES Users(UserID)
);
GO

-- ============================================
-- 7. Table: Orders
-- Stores customer orders (without delivery)
-- ============================================
CREATE TABLE Orders (
    OrderID INT IDENTITY(1,1) PRIMARY KEY,
    UserID INT NOT NULL,
    OrderDate DATETIME DEFAULT GETDATE(),
    Status NVARCHAR(20) CHECK(Status IN ('Pending', 'Accepted', 'Declined', 'Completed')) DEFAULT 'Pending',
    TotalAmount DECIMAL(10,2) DEFAULT 0,
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    CONSTRAINT FK_Orders_Users FOREIGN KEY(UserID) REFERENCES Users(UserID)
);
GO

-- ============================================
-- 8. Table: OrderItems
-- Stores each menu item in an order with customization
-- ============================================
CREATE TABLE OrderItems (
    OrderItemID INT IDENTITY(1,1) PRIMARY KEY,
    OrderID INT NOT NULL,
    MenuItemID INT NOT NULL,
    Quantity INT DEFAULT 1,
    Customization NVARCHAR(MAX), 
    Price DECIMAL(10,2) NOT NULL,
    CONSTRAINT FK_OrderItems_Orders FOREIGN KEY(OrderID) REFERENCES Orders(OrderID),
    CONSTRAINT FK_OrderItems_MenuItems FOREIGN KEY(MenuItemID) REFERENCES MenuItems(MenuItemID)
);
GO

-- ============================================
-- 9. Table: Feedback
-- ============================================
CREATE TABLE Feedback (
    FeedbackID INT IDENTITY(1,1) PRIMARY KEY,
    UserID INT NOT NULL,
    Category NVARCHAR(50), -- e.g., Service, Food, Ambiance
    Rating INT CHECK(Rating BETWEEN 1 AND 5),
    Comments NVARCHAR(1000),
    Response NVARCHAR(1000),
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    CONSTRAINT FK_Feedback_Users FOREIGN KEY(UserID) REFERENCES Users(UserID)
);
GO

-- ============================================
-- 10. Table: Notifications
-- ============================================
CREATE TABLE Notifications (
    NotificationID INT IDENTITY(1,1) PRIMARY KEY,
    UserID INT NOT NULL,
    Message NVARCHAR(500),
    IsRead BIT DEFAULT 0,
    CreatedAt DATETIME DEFAULT GETDATE(),
    CONSTRAINT FK_Notifications_Users FOREIGN KEY(UserID) REFERENCES Users(UserID)
);
GO

ALTER TABLE Notifications
ADD TargetRole NVARCHAR(20) NULL;

ALTER TABLE Notifications
ADD NotificationType NVARCHAR(50);

ALTER TABLE Notifications
ADD CONSTRAINT CK_TargetRole
CHECK (TargetRole IN ('Admin','Customer'));
DELETE FROM Notifications;
DBCC CHECKIDENT ('Notifications', RESEED, 0);



INSERT INTO Notifications (UserID, Message, TargetRole, NotificationType)
VALUES (2,'New order placed','Admin','Order');
INSERT INTO Notifications (UserID, Message, TargetRole, NotificationType)
VALUES (2,'Your order has been accepted','Customer','Order');
INSERT INTO Notifications (UserID, Message, TargetRole, NotificationType)
VALUES (2,'panding','Customer','Order');



-- ============================================
-- 12. Table: Payments
-- ============================================
CREATE TABLE Payments (
    PaymentID INT IDENTITY(1,1) PRIMARY KEY,
    OrderID INT NOT NULL,
    UserID INT NOT NULL,
    PaymentAmount DECIMAL(10,2) NOT NULL,
    PaymentMethod NVARCHAR(50), -- e.g., Card, Cash, Online
    PaymentStatus NVARCHAR(20) CHECK(PaymentStatus IN ('Pending', 'Completed', 'Failed')) DEFAULT 'Pending',
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE(),
    CONSTRAINT FK_Payments_Orders FOREIGN KEY(OrderID) REFERENCES Orders(OrderID),
    CONSTRAINT FK_Payments_Users FOREIGN KEY(UserID) REFERENCES Users(UserID)
);
GO

-- ============================================
-- 13. Table: CafeSettings
-- ============================================
CREATE TABLE CafeSettings (
    ID INT PRIMARY KEY IDENTITY(1,1),
    CafeName VARCHAR(100),
    Address VARCHAR(255),
    Latitude DECIMAL(10,6),
    Longitude DECIMAL(10,6)
);

INSERT INTO CafeSettings (CafeName, Address, Latitude, Longitude)
VALUES ('Cafe AR', 'Colombo Sri Lanka', 6.9271, 79.8612);
GO

ALTER TABLE CafeSettings
ADD Phone VARCHAR(20),
    Email VARCHAR(150),
    AboutText NVARCHAR(500),
    OpeningHours NVARCHAR(100);

UPDATE CafeSettings
SET 
Phone = '+94 711234567',
Email = 'demo@gmail.com',
AboutText = 'Carries Cafe serves premium coffee made with fresh organic ingredients.',
OpeningHours = 'Everyday 10:00 AM - 10:00 PM'
WHERE ID = 1;

-- ============================================
-- 14. Table: CafeTables & TableSeats
-- ============================================
CREATE TABLE CafeTables (
    TableID INT IDENTITY PRIMARY KEY,
    TableName NVARCHAR(50) UNIQUE NOT NULL
);

CREATE TABLE TableSeats (
    SeatID INT IDENTITY PRIMARY KEY,
    TableID INT NOT NULL,
    SeatNumber NVARCHAR(10) NOT NULL,
    CONSTRAINT FK_TableSeats_Table FOREIGN KEY (TableID) REFERENCES CafeTables(TableID)
        ON DELETE CASCADE
);
GO

-- ============================================
-- 15. Table: PortionSizes
-- ============================================
CREATE TABLE PortionSizes (
    PortionID INT IDENTITY PRIMARY KEY,
    Category NVARCHAR(50),        -- e.g., Pizza, Cake, Drink
    PortionName NVARCHAR(50)      -- e.g., Small, Medium, Large
);
GO

-- ============================================
-- 16. Table: Customizations
-- ============================================
CREATE TABLE Customizations (
    CustomizationID INT IDENTITY PRIMARY KEY,
    MenuItemID INT NULL,          -- If NULL, applies to Category
    Category NVARCHAR(50) NULL,
    Type NVARCHAR(50) NOT NULL,   -- 'Ingredient', 'Topping'
    Name NVARCHAR(100) NOT NULL,
    Price DECIMAL(10,2) DEFAULT 0,
    CONSTRAINT FK_Customizations_MenuItems FOREIGN KEY(MenuItemID) REFERENCES MenuItems(MenuItemID)
);
GO
-- 2️⃣ Add two new columns: one for Android (.glb) and one for iPhone (.usdz)
ALTER TABLE Customizations
ADD ModelGLB NVARCHAR(255) NULL,
    ModelUSDZ NVARCHAR(255) NULL;




-- ============================================
-- 17. Indexes
-- ============================================
CREATE INDEX IX_Orders_UserID ON Orders(UserID);
CREATE INDEX IX_Reservations_UserID ON Reservations(UserID);
CREATE INDEX IX_Feedback_UserID ON Feedback(UserID);
CREATE INDEX IX_OrderItems_OrderID ON OrderItems(OrderID);
CREATE INDEX IX_MenuItems_Category ON MenuItems(Category);
GO


-- ============================================
-- Sample Inserts for CafeManagementAR (without delivery)
-- ============================================

-- 1. Users (Admins + Customers + Walk-ins)
INSERT INTO Users (FullName, ContactNumber, Address, Username, PasswordHash, Role, Email)
VALUES 
('Admin One', '0712345678', 'Admin Street', 'admin1', 'hashedpassword', 'Admin', 'admin1@example.com'),
('John Doe', '0771234567', '123 Main St', 'john', 'hashedpassword', 'Customer', 'john@example.com'),
('Jane Smith', '0777654321', '456 Lake Rd', 'jane_smith', 'hashedpassword', 'Customer', 'jane@example.com'),
('Walk-in Customer', NULL, NULL, 'walkin', 'walkinpass', 'Customer', 'walkin@example.com');
GO
UPDATE Users
SET PasswordHash = 'admin123'
WHERE Role = 'Admin';


INSERT INTO Users (FullName, ContactNumber, Address, Username, PasswordHash, Role, Email)
VALUES 
('Tom', '0712345855', '123 Street', 'tom', 'tom2000', 'Customer', 'tom@example.com');



-- 3. CafeTables & TableSeats
INSERT INTO CafeTables (TableName)
VALUES ('Table 1'), ('Table 2'), ('Table 3');
GO

INSERT INTO TableSeats (TableID, SeatNumber)
VALUES 
(1, 'A1'), (1, 'A2'), (1, 'A3'),
(2, 'B1'), (2, 'B2'),
(3, 'C1'), (3, 'C2'), (3, 'C3'), (3, 'C4');
GO


-- ===============================
-- 2. Insert Menu Items
-- ===============================
INSERT INTO MenuItems (FoodName, Description, Price, Category, Ingredients, PortionSize, NutritionalInfo)
VALUES
('Margherita Pizza', 'Classic Italian pizza with fresh mozzarella and basil', 650.00, 'Pizza', 'Dough, Tomato Sauce, Mozzarella, Basil', 'Medium', '300 kcal'),
('Pepperoni Pizza', 'Spicy pepperoni with mozzarella and tomato sauce', 750.00, 'Pizza', 'Dough, Tomato Sauce, Mozzarella, Pepperoni', 'Medium', '350 kcal'),
('Veggie Burger', 'Grilled veggie patty with lettuce, tomato, and cheese', 480.00, 'Burger', 'Veggie Patty, Lettuce, Tomato, Cheese, Bun', 'Single', '250 kcal'),
('Classic Cheeseburger', 'Juicy beef patty with cheddar, lettuce, and tomato', 520.00, 'Burger', 'Beef Patty, Cheddar, Lettuce, Tomato, Bun', 'Single', '400 kcal'),
('Cappuccino', 'Rich espresso topped with steamed milk foam', 250.00, 'Drinks', 'Espresso, Milk, Foam', 'Regular', '120 kcal'),
('Latte', 'Smooth espresso with steamed milk and light foam', 270.00, 'Drinks', 'Espresso, Milk, Foam', 'Regular', '130 kcal'),
('Caesar Salad', 'Fresh romaine lettuce with Caesar dressing and croutons', 350.00, 'Salad', 'Romaine Lettuce, Caesar Dressing, Croutons, Parmesan', 'Single', '180 kcal'),
('Grilled Chicken Sandwich', 'Grilled chicken breast with lettuce and tomato', 450.00, 'Sandwich', 'Chicken Breast, Lettuce, Tomato, Bun, Mayo', 'Single', '300 kcal'),
('Chocolate Muffin', 'Soft chocolate muffin with chocolate chips', 200.00, 'Dessert', 'Flour, Cocoa, Sugar, Eggs, Chocolate Chips', 'Single', '250 kcal'),
('Tiramisu', 'Classic Italian dessert with mascarpone and coffee', 380.00, 'Dessert', 'Mascarpone, Coffee, Ladyfingers, Cocoa Powder, Sugar', 'Single', '330 kcal');

-- ===============================
-- 3. Insert Portion Sizes
-- ===============================
INSERT INTO PortionSizes (Category, PortionName)
VALUES
('Pizza', 'Small'), ('Pizza', 'Medium'), ('Pizza', 'Large'),
('Burger', 'Single'), ('Burger', 'Double'),
('Drinks', 'Regular'), ('Drinks', 'Large'),
('Salad', 'Single'), ('Sandwich', 'Single'), ('Dessert', 'Single');

-- ===============================
-- 4. Insert Customizations (Toppings / Ingredients)
-- ===============================
INSERT INTO Customizations (MenuItemID, Category, Type, Name, Price)
VALUES
-- Margherita Pizza
(1, 'Pizza', 'Topping', 'Extra Cheese', 50.00),
(1, 'Pizza', 'Topping', 'Olives', 30.00),
(1, 'Pizza', 'Topping', 'Basil', 20.00),

-- Pepperoni Pizza
(2, 'Pizza', 'Topping', 'Extra Pepperoni', 60.00),
(2, 'Pizza', 'Topping', 'Mushrooms', 40.00),

-- Veggie Burger
(3, 'Burger', 'Ingredient', 'Add Lettuce', 10.00),
(3, 'Burger', 'Ingredient', 'Add Tomato', 10.00),
(3, 'Burger', 'Ingredient', 'Add Cheese', 25.00),

-- Classic Cheeseburger
(4, 'Burger', 'Ingredient', 'Extra Cheese', 25.00),
(4, 'Burger', 'Ingredient', 'Bacon', 50.00),

-- Drinks
(5, 'Drinks', 'Ingredient', 'Soy Milk', 20.00),
(6, 'Drinks', 'Ingredient', 'Almond Milk', 25.00),

-- Caesar Salad
(7, 'Salad', 'Ingredient', 'Extra Parmesan', 15.00),
(7, 'Salad', 'Ingredient', 'Croutons', 10.00),

-- Grilled Chicken Sandwich
(8, 'Sandwich', 'Ingredient', 'Add Mayo', 10.00),
(8, 'Sandwich', 'Ingredient', 'Extra Chicken', 50.00),

-- Chocolate Muffin
(9, 'Dessert', 'Topping', 'Chocolate Chips', 15.00),

-- Tiramisu
(10, 'Dessert', 'Topping', 'Cocoa Powder', 10.00);

-- 6. Reservations
INSERT INTO Reservations (UserID, SeatNumber, ReservationDate, FullName, Email, Status)
VALUES
(2, 'A1', '2026-02-10 19:00:00', 'John Doe', 'john@example.com', 'Accepted'),
(3, 'B1', '2026-02-11 12:30:00', 'Jane Smith', 'jane@example.com', 'Pending'),
(NULL, 'C1', '2026-02-11 13:00:00', 'Walk-in Guest', 'guest@example.com', 'Pending');
GO


-- 7. Orders
INSERT INTO Orders (UserID, Status, TotalAmount)
VALUES
(2, 'Pending', 550.00),
(3, 'Accepted', 350.00);
GO

-- 8. OrderItems
INSERT INTO OrderItems (OrderID, MenuItemID, Quantity, Customization, Price)
VALUES
(1, 1, 1, 'Extra Cheese', 550.00),
(2, 2, 1, NULL, 350.00);
GO

-- 9. Feedback
INSERT INTO Feedback (UserID, Category, Rating, Comments, Response)
VALUES
(2, 'Food', 5, 'Excellent pizza!', 'Thank you!'),
(3, 'Service', 4, 'Friendly staff', 'We appreciate your feedback');
GO

-- 10. Notifications
INSERT INTO Notifications (UserID, Message, IsRead)
VALUES
(2, 'Your order has been received.', 0),
(3, 'Your table reservation is confirmed.', 0);
GO



-- 12. Payments
INSERT INTO Payments (OrderID, UserID, PaymentAmount, PaymentMethod, PaymentStatus)
VALUES
(1, 2, 550.00, 'Card', 'Pending'),
(2, 3, 350.00, 'Cash', 'Completed');
GO




-- ============================================
-- Table: CategoryARModels
-- Stores base prices & AR models per category
-- ============================================
CREATE TABLE CategoryARModels (
    Category NVARCHAR(50) PRIMARY KEY,   -- unique category name
    BasePrice DECIMAL(10,2) NOT NULL,    -- base price for this category
    ModelGLB NVARCHAR(255) NULL,         -- Android / GLB
    ModelUSDZ NVARCHAR(255) NULL,        -- iOS / USDZ
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE()
);
GO

INSERT INTO CategoryARModels (Category, BasePrice)
VALUES
('Burger', 300.00),
('Dessert', 250.00),
('Drinks', 150.00),
('Salad', 200.00),
('Sandwich', 300.00);

ALTER TABLE MenuItems
ADD CONSTRAINT FK_MenuItems_Category
FOREIGN KEY (Category) REFERENCES CategoryARModels(Category);

ALTER TABLE Customizations
ADD CONSTRAINT FK_Customizations_Category
FOREIGN KEY (Category) REFERENCES CategoryARModels(Category);


UPDATE CategoryARModels
SET BasePrice = 0
WHERE Category <> 'cake';

SELECT * FROM OrderItems
ALTER TABLE OrderItems
DROP CONSTRAINT FK_OrderItems_MenuItems; -- drop FK first

ALTER TABLE OrderItems
ALTER COLUMN MenuItemID INT NULL;

select * from OrderItems;

ALTER TABLE OrderItems
ADD Category NVARCHAR(50) NULL;

-- Optionally, keep a foreign key to CategoryARModels
ALTER TABLE OrderItems
ADD CONSTRAINT FK_OrderItems_CategoryAR FOREIGN KEY (Category) REFERENCES CategoryARModels(Category);

INSERT INTO OrderItems (OrderID, MenuItemID, Category, Quantity, Customization, Price)
VALUES (1, NULL, 'Burger', 1, '{"ingredients":[],"toppings":[],"notes":""}', 300.00)


INSERT INTO OrderItems (OrderID, MenuItemID, Category, Quantity, Customization, Price)
VALUES (17, 1, NULL, 2, '{"cheese":"extra"}', 350);



