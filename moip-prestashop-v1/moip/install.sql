CREATE TABLE IF NOT EXISTS `PREFIX_moip_order` (
      `id_order` int(10) NULL,
      `id_cart` int(10) NOT NULL,
      `id_transaction` varchar(32) NOT NULL,
      `token_transaction` varchar(60) NOT NULL,
      `payment_url` varchar(255) NULL,
      `payment_form` varchar(255) NULL,
      `payment_form_institution` varchar(255) NULL,
      `payment_code` varchar(14) NULL,
      `payment_status` int(10) NULL,
      `payment_value` decimal(17,2) NULL,
      `payment_classification` varchar(255) NULL,
      PRIMARY KEY (`id_transaction`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


