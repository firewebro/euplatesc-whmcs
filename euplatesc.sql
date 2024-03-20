--
-- Table structure for table `euplatesc`
--

CREATE TABLE IF NOT EXISTS `euplatesc` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `invoiceid` int(20) NOT NULL,
  `cart_id` longtext CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `amount` varchar(200) NOT NULL,
  `status` enum('validated','invalidated') NOT NULL DEFAULT 'invalidated',
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoiceid` (`invoiceid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=12 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
