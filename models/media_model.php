<?php

/**
 * Liste tous les médias avec pagination 
 */
function media_get_all($limit = 20, $offset = 0) {
    $sql = "SELECT * FROM medias ORDER BY created_at DESC, id DESC LIMIT ?, ?";
    return db_select($sql, [(int)$offset, (int)$limit]);
}

/**
 * Compte le nombre total de médias 
 */
function media_count_all() {
    $row = db_select_one("SELECT COUNT(*) AS c FROM medias");
    return (int)($row['c'] ?? 0);
}

/**
 * Récupère un média par son identifiant 
 */
function media_get_by_id($id) {
    return db_select_one("SELECT * FROM medias WHERE id = ? LIMIT 1", [(int)$id]);
}

/**
 * Renvoie la liste blanche des genres acceptés (côté app).
 */
function media_allowed_genres(): array {
    return [
        'Fantasy','Science-fiction','Policier','Romance','Horreur','Biographie',
        'Histoire','Aventure','Animation','RPG','Course','Action','Comédie','Drame'
    ];
}

/**
 * Crée un média 
 */
function media_create($data) {
    $sql = "INSERT INTO medias
        (type, title, genre, description, stock, cover_path, author, isbn, pages,
         publication_year, producer, duration_min, classification, release_year,
         editor, plateforme, age_min, total_copies)
        VALUES
        (:type, :title, :genre, :description, :stock, :cover_path, :author, :isbn, :pages,
         :publication_year, :producer, :duration_min, :classification, :release_year,
         :editor, :plateforme, :age_min, :total_copies)";

    $initial = max(1, (int)($data['stock'] ?? 1));

    $params = [
        ':type'             => $data['type'],
        ':title'            => $data['title'],
        ':genre'            => $data['genre'],
        ':description'      => $data['description'] ?? null,
        ':stock'            => $initial,
        ':cover_path'       => $data['cover_path'] ?? null,
        ':author'           => $data['author'] ?? null,
        ':isbn'             => $data['isbn'] ?? null,
        ':pages'            => $data['pages'] ?? null,
        ':publication_year' => $data['publication_year'] ?? null,
        ':producer'         => $data['producer'] ?? null,
        ':duration_min'     => $data['duration_min'] ?? null,
        ':classification'   => $data['classification'] ?? null,
        ':release_year'     => $data['release_year'] ?? null,
        ':editor'           => $data['editor'] ?? null,
        ':plateforme'       => $data['plateforme'] ?? null,
        ':age_min'          => $data['age_min'] ?? null,
        ':total_copies'     => $initial,
    ];
    return db_execute($sql, $params);
}

/**
 * Met à jour un média 
 */
function media_update(int $id, array $data) {
    $sql = "UPDATE medias SET
                type = :type,
                title = :title,
                genre = :genre,
                description = :description,
                stock = :stock,
                cover_path = :cover_path,
                author = :author,
                isbn = :isbn,
                pages = :pages,
                publication_year = :publication_year,
                producer = :producer,
                duration_min = :duration_min,
                classification = :classification,
                release_year = :release_year,
                editor = :editor,
                plateforme = :plateforme,
                age_min = :age_min
            WHERE id = :id";

    $params = [
        ':id'               => $id,
        ':type'             => $data['type'],
        ':title'            => $data['title'],
        ':genre'            => $data['genre'],
        ':description'      => $data['description'] ?? null,
        ':stock'            => max(0, (int)($data['stock'] ?? 0)),
        ':cover_path'       => $data['cover_path'] ?? null,
        ':author'           => $data['author'] ?? null,
        ':isbn'             => $data['isbn'] ?? null,
        ':pages'            => $data['pages'] ?? null,
        ':publication_year' => $data['publication_year'] ?? null,
        ':producer'         => $data['producer'] ?? null,
        ':duration_min'     => $data['duration_min'] ?? null,
        ':classification'   => $data['classification'] ?? null,
        ':release_year'     => $data['release_year'] ?? null,
        ':editor'           => $data['editor'] ?? null,
        ':plateforme'       => $data['plateforme'] ?? null,
        ':age_min'          => $data['age_min'] ?? null,
    ];
    return db_execute($sql, $params);
}

/**
 * Recherche dans le catalogue avec filtres le trie et la pagination
 */
function media_search(array $filters, int $limit, int $offset): array {
    [$where, $params] = media_build_where($filters);
    $orderSql = media_order_sql($filters['order'] ?? '');

    $sql = "SELECT
                id,
                type,
                title            AS titre,
                genre,
                COALESCE(description, '')        AS description,
                COALESCE(stock, 0)               AS stock,
                COALESCE(cover_path, '')         AS cover_path,
                COALESCE(created_at, '')         AS created_at,
                COALESCE(author, '')             AS auteur,
                COALESCE(isbn, '')               AS isbn,
                COALESCE(pages, 0)               AS pages,
                COALESCE(publication_year, 0)    AS annee_publication,
                COALESCE(producer, '')           AS realisateur,
                COALESCE(duration_min, 0)        AS duree,
                COALESCE(classification, '')     AS classification,
                COALESCE(release_year, 0)        AS annee_sortie,
                COALESCE(editor, '')             AS editeur,
                COALESCE(plateforme, '')         AS plateforme,
                COALESCE(age_min, 0)             AS age_min,
                COALESCE(total_copies, 0)        AS total_copies 
            /* https://dev.mysql.com/doc/refman/8.0/en/control-flow-functions.html#function_coalesce */
            FROM medias
            $where
            $orderSql
            LIMIT ?, ?";

    $params[] = (int)$offset;
    $params[] = (int)$limit;

    return db_select($sql, $params);
}

/**
 * Compte le nombre de résultats pour une recherche filtrée pour la pagination
 */
function media_count_search(array $filters): int {
    [$where, $params] = media_build_where($filters);
    $row = db_select_one("SELECT COUNT(*) AS c FROM medias $where", $params);
    return (int)($row['c'] ?? 0);
}

/**
 * Construit dynamiquement la clause WHERE à partir des filtres https://dev.mysql.com/doc/refman/8.0/en/non-typed-operators.html
 */
function media_build_where(array $filters): array {
    $w = [];
    $p = [];

    if (!empty($filters['q'])) {
        $q = '%' . $filters['q'] . '%';
        $w[] = "(title LIKE ? OR description LIKE ? OR author LIKE ? OR producer LIKE ? OR editor LIKE ? OR isbn LIKE ?)";
        array_push($p, $q,$q,$q,$q,$q,$q);
    }

    if (!empty($filters['type']) && in_array($filters['type'], ['livre','film','jeu'], true)) {
        $w[] = "type = ?";
        $p[] = $filters['type'];
    }

    if (!empty($filters['genre'])) {
        $w[] = "genre = ?";
        $p[] = $filters['genre'];
    }

    if (!empty($filters['available'])) {
        $w[] = "stock > 0";
    }

    if (!empty($filters['classification'])) {
        $w[] = "classification = ?";
        $p[] = $filters['classification'];
    }

    if (!empty($filters['plateforme'])) {
        $w[] = "plateforme = ?";
        $p[] = $filters['plateforme'];
    }

    if (isset($filters['age_min']) && $filters['age_min'] !== '') {
        $w[] = "age_min = ?";
        $p[] = (int)$filters['age_min'];
    }


    $ym = isset($filters['year_min']) && $filters['year_min'] !== '' ? (int)$filters['year_min'] : null;
    $yM = isset($filters['year_max']) && $filters['year_max'] !== '' ? (int)$filters['year_max'] : null;

    if ($ym !== null && $yM !== null) {
        $w[] = "(
                    (publication_year IS NOT NULL AND publication_year BETWEEN ? AND ?)
                 OR (release_year      IS NOT NULL AND release_year      BETWEEN ? AND ?)
                )";
        array_push($p, $ym, $yM, $ym, $yM);
    } elseif ($ym !== null) {
        $w[] = "(
                    (publication_year IS NOT NULL AND publication_year >= ?)
                 OR (release_year      IS NOT NULL AND release_year      >= ?)
                )";
        array_push($p, $ym, $ym);
    } elseif ($yM !== null) {
        $w[] = "(
                    (publication_year IS NOT NULL AND publication_year <= ?)
                 OR (release_year      IS NOT NULL AND release_year      <= ?)
                )";
        array_push($p, $yM, $yM);
    }

    $where = empty($w) ? '' : ('WHERE ' . implode(' AND ', $w));
    return [$where, $p];
}

/**
 * Retourne la clause ORDER BY selon la demande titre,date ou stock.
 */
function media_order_sql(string $order): string {
    switch ($order) {
        case 'title_asc':  return "ORDER BY title ASC, id DESC";
        case 'title_desc': return "ORDER BY title DESC, id DESC";
        case 'date_asc':   return "ORDER BY created_at ASC, id ASC";
        case 'stock_desc': return "ORDER BY stock DESC, id DESC";
        case 'stock_asc':  return "ORDER BY stock ASC, id ASC";
        default:           return "ORDER BY created_at DESC, id DESC";
    }
}

/**
 * Upload d’une cover avec validations et redimension au max 300x400.
 */
function media_handle_cover_upload(string $field_name): array {
    if (empty($_FILES[$field_name]['name'])) {
        return ['path' => null];
    }

    $file = $_FILES[$field_name];
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => "Erreur d'upload (code {$file['error']})."];
    }

    $tmp  = $file['tmp_name'];
    $size = (int)$file['size'];
    if ($size <= 0) return ['error' => "Fichier vide."];
    if ($size > 2 * 1024 * 1024) return ['error' => "Image trop volumineuse (max 2 Mo)."];

    $finfo = finfo_open(FILEINFO_MIME_TYPE); //https://www.php.net/finfo_open
    $mime  = finfo_file($finfo, $tmp);
    finfo_close($finfo);

    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif'];
    if (!isset($allowed[$mime])) return ['error' => "Format non supporté (JPG/PNG/GIF)."];

    [$w, $h] = @getimagesize($tmp); // https://www.php.net/getimagesize
    if (!$w || !$h) return ['error' => "Image invalide."];
    if ($w < 100 || !$h || $h < 100) return ['error' => "Dimensions minimales: 100×100 px."];

    $upload_dir_fs   = rtrim(PUBLIC_PATH, '/\\') . '/uploads/covers/';
    $upload_dir_http = rtrim(url('uploads/covers/'), '/');

    if (!is_dir($upload_dir_fs)) @mkdir($upload_dir_fs, 0775, true); // mkdir https://www.php.net/mkdir
    if (!is_writable($upload_dir_fs)) return ['error' => "Dossier d'upload non inscriptible."];

    switch ($mime) {
        case 'image/jpeg': $src = imagecreatefromjpeg($tmp); break; // https://www.php.net/manual/fr/ref.image.php
        case 'image/png':  $src = imagecreatefrompng($tmp);  break;
        case 'image/gif':  $src = imagecreatefromgif($tmp);  break;
        default: return ['error' => "Format non supporté."];
    }
    if (!$src) return ['error' => "Impossible de lire l'image."];

    $maxW = 300; $maxH = 400;
    $ratio = min($maxW / $w, $maxH / $h, 1);
    $newW = (int)floor($w * $ratio);
    $newH = (int)floor($h * $ratio);

    $dst = imagecreatetruecolor($newW, $newH);
    if ($mime !== 'image/jpeg') {
        imagecolortransparent($dst, imagecolorallocatealpha($dst, 0,0,0,127));
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0,0,0,0, $newW,$newH, $w,$h); // https://www.php.net/imagecopyresampled

    $name = 'cov_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime]; // https://www.php.net/random_bytes
    $dest = $upload_dir_fs . $name;

    $ok = false;
    if ($mime === 'image/jpeg') $ok = imagejpeg($dst, $dest, 88); // https://www.php.net/imagejpeg 
    if ($mime === 'image/png')  $ok = imagepng($dst,  $dest, 6);
    if ($mime === 'image/gif')  $ok = imagegif($dst,  $dest);

    imagedestroy($dst);
    imagedestroy($src);

    if (!$ok) return ['error' => "Échec de l'enregistrement de l'image."];
    return ['path' => $upload_dir_http . '/' . $name];
}

/**
 * Remplace la cover d’un média et supprime l’ancienne si elle était locale
 */
function media_replace_cover(int $media_id, string $field_name): array {
    $media = media_get_by_id($media_id);
    if (!$media) return ['error' => "Média introuvable."];

    $u = media_handle_cover_upload($field_name);
    if (!empty($u['error'])) return $u;
    if (empty($u['path']))   return ['error' => "Aucune image fournie."];

    $old = $media['cover_path'] ?? null;
    $ok = db_execute("UPDATE medias SET cover_path = ? WHERE id = ?", [$u['path'], $media_id]);
    if (!$ok) return ['error' => "Échec de mise à jour de l’image."];

    if ($old) {
        $prefix = rtrim(url('uploads/covers/'), '/');
        if (strpos($old, $prefix) === 0) {
            $fn = rtrim(PUBLIC_PATH, '/\\') . str_replace(url(''), '', $old);
            @unlink($fn); https://www.php.net/unlink
        }
    }
    return ['path' => $u['path']];
}

/**
 * Supprime la cover 
 */
function media_clear_cover(int $media_id): array {
    $media = media_get_by_id($media_id);
    if (!$media) return ['ok'=>false, 'error'=>"Média introuvable."];

    $old = $media['cover_path'] ?? null;
    $ok = db_execute("UPDATE medias SET cover_path = NULL WHERE id = ?", [$media_id]);
    if (!$ok) return ['ok'=>false, 'error'=>"Échec de la mise à jour de l'image."];

    if ($old) {
        $prefix = rtrim(url('uploads/covers/'), '/');
        if (strpos($old, $prefix) === 0) {
            $fn = rtrim(PUBLIC_PATH, '/\\') . str_replace(url(''), '', $old);
            @unlink($fn); https://www.php.net/unlink
        }
    }

    return ['ok'=>true];
}

/**
 * Listing secondaire pour la pagination et COALESCE pour ne pas avoir les nulls
 */
function media_get_all2($limit = null, $offset = 0) {
    $sql = "SELECT
                id,
                type,
                title,
                genre,
                COALESCE(description, '')      AS description,
                COALESCE(created_at, '')       AS created_at,
                COALESCE(author, '')           AS author,
                COALESCE(isbn, '')             AS isbn,
                COALESCE(pages, 0)             AS pages,
                COALESCE(publication_year, 0)  AS publication_year,
                COALESCE(producer, '')         AS producer,
                COALESCE(duration_min, 0)      AS duration_min,
                COALESCE(classification, '')   AS classification,
                COALESCE(release_year, 0)      AS release_year,
                COALESCE(editor, '')           AS editor,
                COALESCE(plateforme, '')       AS plateforme,
                COALESCE(age_min, 0)           AS age_min,
                COALESCE(stock, 0)             AS stock
              /*  https://dev.mysql.com/doc/refman/8.0/en/control-flow-functions.html#function_coalesce */
            FROM medias
            ORDER BY created_at DESC, id DESC";
    if ($limit !== null) {
        $sql .= " LIMIT " . (int)$offset . ", " . (int)$limit;
    }
    return db_select($sql);
}

/**
 * Compte le total des médias pour les KPIs.
 */
function count_medias() {
    $query = "SELECT COUNT(*) as total FROM medias";
    $result = db_select_one($query);
    return $result['total'] ?? 0;
}
/** 
 * Nombre de films. — WHERE type='film' (filtre simple) 
 */
function count_medias_movie() {
    $query = "SELECT COUNT(*) as total FROM medias WHERE type='film'";
    $result = db_select_one($query);
    return $result['total'] ?? 0;
}
/** 
 * Nombre de livres. — WHERE type='livre' 
 */
function count_medias_book() {
    $query = "SELECT COUNT(*) as total FROM medias WHERE type='livre'";
    $result = db_select_one($query);
    return $result['total'] ?? 0;
}
/** 
*Nombre de jeux
*/
function count_medias_game() {
    $query = "SELECT COUNT(*) as total FROM medias WHERE type='jeu'";
    $result = db_select_one($query);
    return $result['total'] ?? 0;
}

/**
 * Supprime un média par son id
 */
function delete_media($id) {
    $query = "DELETE FROM medias WHERE id = ?";
    return db_execute($query, [$id]);
}

/**
 * Liste admin avec filtres et trie avec pagination 
 */
function media_search_admin(array $filters, int $limit, int $offset, string $sort = 'created_at', string $dir = 'DESC'): array {
    [$where, $params] = media_build_where($filters);

    $columns = [
        'id'               => 'id',
        'type'             => 'type',
        'title'            => 'title',
        'genre'            => 'genre',
        'created_at'       => 'created_at',
        'author'           => 'author',
        'isbn'             => 'isbn',
        'pages'            => 'pages',
        'publication_year' => 'publication_year',
        'producer'         => 'producer',
        'duration_min'     => 'duration_min',
        'classification'   => 'classification',
        'release_year'     => 'release_year',
        'editor'           => 'editor',
        'plateforme'       => 'plateforme',
        'age_min'          => 'age_min',
        'stock'            => 'stock',
    ];

    $sort = strtolower($sort);                 // strtolower https://www.php.net/strtolower
    if (!isset($columns[$sort])) $sort = 'created_at';
    $dir  = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC'; // strtoupper https://www.php.net/strtoupper

    $order_sql = "ORDER BY {$columns[$sort]} $dir, id DESC";

    $sql = "SELECT
                id, type, title, genre, description, created_at,
                author, isbn, pages, publication_year,
                producer, duration_min, classification, release_year,
                editor, plateforme, age_min, stock
            FROM medias
            $where
            $order_sql
            LIMIT ?, ?";

    $params[] = (int)$offset;
    $params[] = (int)$limit;

    return db_select($sql, $params);
}
