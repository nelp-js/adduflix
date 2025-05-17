CREATE TABLE Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_watched_at DATETIME
);

CREATE TABLE Subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_type ENUM('Monthly', 'Yearly') NOT NULL,
    start_date DATE NOT NULL,
    expiry_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES Users(id)
);

CREATE TABLE Payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subscription_id INT,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    payment_method VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES Users(id),
    FOREIGN KEY (subscription_id) REFERENCES Subscriptions(id)
);

CREATE TABLE Content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    genre VARCHAR(100),
    release_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    total_views INT DEFAULT 0
);

CREATE TABLE ViewingHistory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_id INT NOT NULL,
    watch_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id),
    FOREIGN KEY (content_id) REFERENCES Content(id)
);

CREATE TABLE Reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    review_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id),
    FOREIGN KEY (content_id) REFERENCES Content(id)
);

DELIMITER $$

CREATE TRIGGER set_subscription_expiry
BEFORE INSERT ON Subscriptions
FOR EACH ROW
BEGIN
  IF NEW.plan_type = 'Monthly' THEN
    SET NEW.expiry_date = DATE_ADD(NEW.start_date, INTERVAL 1 MONTH);
  ELSEIF NEW.plan_type = 'Yearly' THEN
    SET NEW.expiry_date = DATE_ADD(NEW.start_date, INTERVAL 1 YEAR);
  END IF;
END$$

DELIMITER ;

INSERT INTO Content (title, description, genre, release_date, status)
VALUES 
('Avengers', 'Superhero action movie', 'Action', '2019-04-26', 'active'),
('Inception', 'Mind-bending sci-fi thriller', 'Sci-Fi', '2010-07-16', 'active'),
('Friends', 'Comedy TV show', 'Comedy', '1994-09-22', 'active');

-- First, drop existing constraints
ALTER TABLE Subscriptions DROP FOREIGN KEY subscriptions_ibfk_1;
ALTER TABLE Payments DROP FOREIGN KEY payments_ibfk_1;
ALTER TABLE Payments DROP FOREIGN KEY payments_ibfk_2;
ALTER TABLE ViewingHistory DROP FOREIGN KEY viewinghistory_ibfk_1;
ALTER TABLE Reviews DROP FOREIGN KEY reviews_ibfk_1;

-- Then recreate them with ON DELETE CASCADE
ALTER TABLE Subscriptions 
ADD CONSTRAINT fk_user_subscription 
FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE;

ALTER TABLE Payments 
ADD CONSTRAINT fk_user_payment 
FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE;

ALTER TABLE Payments 
ADD CONSTRAINT fk_subscription_payment 
FOREIGN KEY (subscription_id) REFERENCES Subscriptions(id) ON DELETE SET NULL;

ALTER TABLE ViewingHistory 
ADD CONSTRAINT fk_user_viewing 
FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE;

ALTER TABLE Reviews 
ADD CONSTRAINT fk_user_review 
FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE;
