-- Copyright (C) 2023 Sylvain Legrand - InfraS - technique@infras.fr
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

CREATE TABLE IF NOT EXISTS llx_discountrule (
  rowid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  label varchar(255) NOT NULL,
  description text,
  tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  entity int(11) NOT NULL DEFAULT 0,
  import_key varchar(14) DEFAULT NULL,
  fk_status integer,
  fk_project int(11) UNSIGNED NOT NULL DEFAULT 0,
  date_creation datetime NOT NULL,
  all_category_product int NOT NULL DEFAULT 0,
  all_category_company int NOT NULL DEFAULT 0,
  all_category_project int NOT NULL DEFAULT 0,
  fk_product int(11) NOT NULL DEFAULT 0,
  fk_country int(11) NOT NULL DEFAULT 0,
  fk_company int(11) NOT NULL DEFAULT 0,
  fk_c_typent int(11) NULL DEFAULT '0',
  from_quantity mediumint(8) UNSIGNED NOT NULL,
  product_price decimal(20,6)  NULL,
  product_reduction_amount decimal(20,6)  NULL,
  reduction decimal(20,6) NOT NULL,
  fk_reduction_tax tinyint(1) NOT NULL DEFAULT 1,
  date_from datetime NULL,
  date_to datetime NULL,
  priority_rank int(3) NOT NULL DEFAULT 0
) ENGINE=innodb;

  
