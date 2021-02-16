CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(225) NOT NULL,
  `first_name` varchar(225) DEFAULT NULL,
  `last_name` varchar(225) DEFAULT NULL,
  `role` smallint(6) DEFAULT '0',
  `phone_number` varchar(13) DEFAULT NULL,
  `is_verified` boolean DEFAULT NULL,
  `token` varchar(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone_number` (`phone_number`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
  id int(11) not null AUTO_INCREMENT,
  name VARCHAR(120),
  description TEXT,
  on_sale boolean,
  category_id int not null,
  image_url varchar(100),
  price VARCHAR(10),
  user_id int not null
)
