-- ======================
-- TABLE utilisateurs
-- ======================
CREATE TABLE utilisateurs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(50),
  email VARCHAR(100) UNIQUE,
  mdp VARCHAR(100),
  role ENUM('admin', 'client') DEFAULT 'client'
);

-- ======================
-- TABLE items
-- ======================
CREATE TABLE items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100),
  description TEXT,
  prix DECIMAL(10,2),
  stock INT,
  image VARCHAR(255)
);

-- ======================
-- TABLE commandes
-- ======================
CREATE TABLE commandes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_utilisateur INT,
  date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id)
);

-- ======================
-- TABLE details_commande
-- ======================
CREATE TABLE details_commande (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_commande INT,
  id_item INT,
  quantite INT DEFAULT 1,
  FOREIGN KEY (id_commande) REFERENCES commandes(id),
  FOREIGN KEY (id_item) REFERENCES items(id)
);

-- ======================
-- TABLE historique_annulation
-- ======================
CREATE TABLE historique_annulation (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_commande INT,
  id_item INT,
  quantite INT,
  date_annulation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ======================
-- PROCEDURE : afficher détails d’une commande
-- ======================
DELIMITER $$
CREATE PROCEDURE afficher_commande(IN idCmd INT)
BEGIN
  SELECT d.id_item, i.nom, i.prix, d.quantite, (i.prix * d.quantite) AS total_ligne
  FROM details_commande d
  JOIN items i ON d.id_item = i.id
  WHERE d.id_commande = idCmd;

  SELECT SUM(i.prix * d.quantite) AS total_commande
  FROM details_commande d
  JOIN items i ON d.id_item = i.id
  WHERE d.id_commande = idCmd;
END$$
DELIMITER ;

-- ======================
-- PROCEDURE : finaliser une commande (vider panier fictif)
-- ======================
DELIMITER $$
CREATE PROCEDURE finaliser_commande(IN idCmd INT)
BEGIN
  -- Exécution fictive ici car panier est géré côté PHP/session
  -- On pourrait marquer la commande comme "payée" ou archiver
  UPDATE commandes SET date_commande = NOW() WHERE id = idCmd;
END$$
DELIMITER ;

-- ======================
-- PROCEDURE : historique commandes client
-- ======================
DELIMITER $$
CREATE PROCEDURE historique_client(IN idUser INT)
BEGIN
  SELECT c.id AS commande_id, c.date_commande, i.nom AS item, d.quantite, i.prix, (i.prix * d.quantite) AS total
  FROM commandes c
  JOIN details_commande d ON c.id = d.id_commande
  JOIN items i ON d.id_item = i.id
  WHERE c.id_utilisateur = idUser
  ORDER BY c.date_commande DESC;
END$$
DELIMITER ;

-- ======================
-- TRIGGER : mettre à jour le stock après commande
-- ======================
DELIMITER $$
CREATE TRIGGER maj_stock_apres_commande
AFTER INSERT ON details_commande
FOR EACH ROW
BEGIN
  UPDATE items
  SET stock = stock - NEW.quantite
  WHERE id = NEW.id_item;
END$$
DELIMITER ;

-- ======================
-- TRIGGER : empêcher commande si stock insuffisant
-- ======================
DELIMITER $$
CREATE TRIGGER verifier_stock_avant_insert
BEFORE INSERT ON details_commande
FOR EACH ROW
BEGIN
  DECLARE quantite_dispo INT;

  SELECT stock INTO quantite_dispo FROM items WHERE id = NEW.id_item;

  IF NEW.quantite > quantite_dispo THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuffisant pour cet article';
  END IF;
END$$
DELIMITER ;

-- ======================
-- TRIGGER : restaurer le stock après annulation
-- ======================
DELIMITER $$
CREATE TRIGGER restaurer_stock_apres_annulation
AFTER DELETE ON details_commande
FOR EACH ROW
BEGIN
  UPDATE items
  SET stock = stock + OLD.quantite
  WHERE id = OLD.id_item;
END$$
DELIMITER ;

-- ======================
-- TRIGGER : historique des annulations
-- ======================
DELIMITER $$
CREATE TRIGGER log_annulation
AFTER DELETE ON details_commande
FOR EACH ROW
BEGIN
  INSERT INTO historique_annulation(id_commande, id_item, quantite)
  VALUES (OLD.id_commande, OLD.id_item, OLD.quantite);
END$$
DELIMITER ;