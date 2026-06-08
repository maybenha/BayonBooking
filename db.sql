-- -- Add deposit_amount column to bookings table

-- USe hotel_booking_system;
-- ALTER TABLE bookings ADD COLUMN deposit_amount DECIMAL(10,2) DEFAULT 0 AFTER total_payment;

-- -- Add payment_reference column
-- ALTER TABLE bookings ADD COLUMN payment_reference VARCHAR(100) NULL AFTER payment_status;

-- -- Add payment_date column
-- ALTER TABLE bookings ADD COLUMN payment_date DATETIME NULL AFTER payment_reference;

-- -- Add checkout_completed_at column
-- ALTER TABLE bookings ADD COLUMN checkout_completed_at DATETIME NULL AFTER booking_status;

-- -- Update existing bookings (set deposit to 30% of total)
-- UPDATE bookings SET deposit_amount = total_payment * 0.30 WHERE deposit_amount = 0;

-- -- Add new booking status values to enum (if using ENUM)
-- -- Note: You may need to modify the column definition
-- ALTER TABLE bookings MODIFY COLUMN booking_status ENUM('pending', 'payment_reserved', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending';

-- -- Add new payment status values
-- ALTER TABLE bookings MODIFY COLUMN payment_status ENUM('deposit_pending', 'deposit_paid', 'fully_paid') DEFAULT 'deposit_pending';

-- UPDATE bookings
-- SET payment_status = 'deposit_pending'
-- WHERE payment_status = 'pending';

-- UPDATE bookings
-- SET payment_status = 'fully_paid'
-- WHERE payment_status = 'paid';

-- USE hotel_booking_system;
-- SELECT * FROM bookings;
-- SELECT * FROM user;
-- SELECT * FROM rooms;