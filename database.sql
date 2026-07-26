-- Database: crm_lead_tracker

-- Database statements removed for shared hosting compatibility
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default password is 'admin123' (hashed)
INSERT INTO `users` (`name`, `email`, `password`) VALUES
('Admin User', 'admin@example.com', 'admin123');

CREATE TABLE IF NOT EXISTS `leads` (
  `id` 
  `name` varchar(100) NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `status` enum('New','Contacted','Qualified','Proposal Sent','Won','Lost') DEFAULT 'New',
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `fk_assigned_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `leads` (`name`, `company`, `email`, `phone`, `source`, `status`, `priority`, `assigned_to`, `notes`) VALUES
('John Doe', 'Acme Corp', 'john@acme.com', '123-456-7890', 'Website', 'New', 'High', 1, 'Initial contact from website.'),
('Jane Smith', 'Tech Solutions', 'jane@techsolutions.com', '098-765-4321', 'Referral', 'Contacted', 'Medium', 1, 'Looking for our CRM product.');
int(11) NOT NULL AUTO_INCREMENT,