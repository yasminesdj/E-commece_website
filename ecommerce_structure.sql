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

DELIMITER $$

-- Procédure 1 : Afficher les détails d’une commande
CREATE PROCEDURE proc_afficher_commande_details(IN p_id_commande INT)
BEGIN
    SELECT dc.id_item, i.nom, dc.quantite, dc.prix_unitaire,
           (dc.quantite * dc.prix_unitaire) AS total_ligne
    FROM details_commande dc
    JOIN items i ON i.id = dc.id_item
    WHERE dc.id_commande = p_id_commande;

    SELECT SUM(dc.quantite * dc.prix_unitaire) AS total_commande
    FROM details_commande dc
    WHERE dc.id_commande = p_id_commande;
END$$

-- Procédure 2 : Finaliser une commande et vider le panier
CREATE PROCEDURE proc_finaliser_commande(IN p_id_utilisateur INT)
BEGIN
    DECLARE v_id_commande INT;
    DECLARE v_total DECIMAL(10,2);

    -- Calcul du total
    SELECT SUM(i.prix * p.quantite) INTO v_total
    FROM panier p
    JOIN items i ON i.id = p.id_item
    WHERE p.id_utilisateur = p_id_utilisateur;

    -- Création de la commande
    INSERT INTO commandes(id_utilisateur, date_commande, total, statut)
    VALUES (p_id_utilisateur, NOW(), v_total, 'validée');

    SET v_id_commande = LAST_INSERT_ID();

    -- Insertion des détails de commande
    INSERT INTO details_commande(id_commande, id_item, quantite, prix_unitaire)
    SELECT v_id_commande, p.id_item, p.quantite, i.prix
    FROM panier p
    JOIN items i ON i.id = p.id_item
    WHERE p.id_utilisateur = p_id_utilisateur;

    -- Vider le panier
    DELETE FROM panier WHERE id_utilisateur = p_id_utilisateur;
END$$

-- Procédure 3 : Historique des commandes d’un client
CREATE PROCEDURE proc_historique_commandes(IN p_id_utilisateur INT)
BEGIN
    SELECT c.id AS id_commande, c.date_commande, c.total, c.statut
    FROM commandes c
    WHERE c.id_utilisateur = p_id_utilisateur
    ORDER BY c.date_commande DESC;
END$$

-- Trigger 1 : Mise à jour du stock après validation d’une commande
CREATE TRIGGER trg_update_stock_after_insert
AFTER INSERT ON details_commande
FOR EACH ROW
BEGIN
    UPDATE items
    SET stock = stock - NEW.quantite
    WHERE id = NEW.id_item;
END$$

-- Trigger 2 : Empêcher l’insertion si la quantité dépasse le stock
CREATE TRIGGER trg_prevent_insert_if_stock_insufficient
BEFORE INSERT ON details_commande
FOR EACH ROW
BEGIN
    DECLARE v_stock INT;
    SELECT stock INTO v_stock FROM items WHERE id = NEW.id_item;
    IF NEW.quantite > v_stock THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuffisant';
    END IF;
END$$

-- Trigger 3 : Restaurer le stock après annulation d’une commande
CREATE TRIGGER trg_restore_stock_after_delete
AFTER DELETE ON details_commande
FOR EACH ROW
BEGIN
    UPDATE items
    SET stock = stock + OLD.quantite
    WHERE id = OLD.id_item;
END$$

-- Trigger 4 : Garder trace des commandes annulées
DELIMITER $$

CREATE TRIGGER trg_log_cancelled_order
BEFORE DELETE ON commandes
FOR EACH ROW
BEGIN
    INSERT INTO historique_annulation(id_commande, id_item, quantite, date_annulation)
    SELECT OLD.id, dc.id_item, dc.quantite, NOW()
    FROM details_commande dc
    WHERE dc.id_commande = OLD.id;
END$$

DELIMITER ;

-- ✅ 1. Création de la table `panier`
DROP TABLE IF EXISTS panier;
CREATE TABLE panier (
    id_utilisateur INT NOT NULL,
    id_item INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1
);



DELIMITER $$

CREATE PROCEDURE proc_finaliser_commande(IN p_id_utilisateur INT)
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE v_id_item INT;
  DECLARE v_quantite INT;
  DECLARE v_prix FLOAT;
  DECLARE v_commande_id INT;

  -- Curseur pour parcourir le panier
  DECLARE c CURSOR FOR
    SELECT id_item, quantite FROM panier WHERE id_utilisateur = p_id_utilisateur;
  
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  START TRANSACTION;

  -- Insérer la commande
  INSERT INTO commandes (id_utilisateur) VALUES (p_id_utilisateur);
  SET v_commande_id = LAST_INSERT_ID();

  OPEN c;

  boucle_panier: LOOP
    FETCH c INTO v_id_item, v_quantite;
    IF done THEN
      LEAVE boucle_panier;
    END IF;

    -- Récupérer le prix unitaire
    SELECT prix INTO v_prix FROM items WHERE id = v_id_item;

    -- Vérifier le stock disponible
    IF v_quantite > (SELECT stock FROM items WHERE id = v_id_item) THEN
      ROLLBACK;
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Quantité demandée dépasse le stock disponible';
    END IF;

    -- Ajouter au détail commande
    INSERT INTO details_commande (id_commande, id_item, quantite, prix_unitaire)
    VALUES (v_commande_id, v_id_item, v_quantite, v_prix);

    -- Mettre à jour le stock
    UPDATE items SET stock = stock - v_quantite WHERE id = v_id_item;
  END LOOP;

  CLOSE c;

  -- Vider le panier
  DELETE FROM panier WHERE id_utilisateur = p_id_utilisateur;

  COMMIT;
END $$

DELIMITER ;
