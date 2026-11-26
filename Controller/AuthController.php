<?php
declare(strict_types=1);

final class AuthController
{
    private const USER_FIELDS = 'id, username, email, role, created_at, phone, profile_photo';
    private const PROFILE_PHOTO_MAX_BYTES = 2097152;
    private const PROFILE_PHOTO_MAX_PIXELS = 25000000;

    public function home(): void
    {
        render('front/home', 'Accueil', ['account' => $this->currentUser(), 'section' => 'home']);
    }

    public function register(string $method): void
    {
        $this->redirectIfSignedIn();
        $values = ['username' => '', 'email' => ''];
        $errors = [];

        if ($method === 'POST') {
            requireCsrf();
            [$values, $password, $errors] = $this->validateUser(false, true);
            if ($password !== requestText($_POST, 'password_confirm')) {
                $errors['password_confirm'] = 'Les deux mots de passe doivent être identiques.';
            }
            if ($errors === []) {
                $errors = $this->writeUser($values + ['role' => 'user'], $password);
                if ($errors === []) {
                    flash('Ton compte a été créé. Tu peux te connecter.');
                    redirect('login');
                }
            }
            http_response_code(422);
        }
        render('front/register', 'Créer un compte', compact('values', 'errors'));
    }

    public function login(string $method): void
    {
        $this->redirectIfSignedIn();
        $email = '';
        $errors = [];

        if ($method === 'POST') {
            requireCsrf();
            $email = trim(requestText($_POST, 'email'));
            $password = requestText($_POST, 'password');

            if (!$this->validEmail($email)) {
                $errors['email'] = 'Entre une adresse e-mail valide.';
            }
            if (!$this->validPassword($password, 1)) {
                $errors['password'] = 'Entre ton mot de passe (72 octets maximum).';
            }

            if ($errors === []) {
                $statement = Database::getConnection()->prepare('SELECT id, role, password FROM users WHERE email = ?');
                $statement->execute([$email]);
                $row = $statement->fetch();

                if ($row && password_verify($password, $row['password'])) {
                    session_regenerate_id(true);
                    $_SESSION = [
                        'user_id' => (int) $row['id'],
                        'role' => $row['role'],
                        'last_activity' => time(),
                    ];
                    redirect($row['role'] === 'admin' ? 'admin' : 'dashboard');
                }
                $errors['general'] = 'Adresse e-mail ou mot de passe incorrect.';
            }
            http_response_code(422);
        }

        render('front/login', 'Connexion', compact('email', 'errors'));
    }

    public function dashboard(string $method): void
    {
        $account = $this->requireAuth();
        $values = ['phone' => $account->getPhone() ?? ''];
        $errors = [];
        $removePhotoRequested = false;

        if ($method === 'POST') {
            requireCsrf();
            $values['phone'] = trim(requestText($_POST, 'phone'));
            $removePhotoRequested = requestText($_POST, 'remove_profile_photo') === '1';
            if (!$this->validPhone($values['phone'])) {
                $errors['phone'] = 'Entre un numéro valide de 6 à 20 chiffres.';
            }

            $newPhoto = null;
            if ($errors === []) {
                [$newPhoto, $photoError] = $this->storeProfilePhoto($_FILES['profile_photo'] ?? []);
                if ($photoError !== null) {
                    $errors['profile_photo'] = $photoError;
                }
            }

            if ($errors === []) {
                $oldPhoto = $account->getProfilePhoto();
                $profilePhoto = $newPhoto ?? ($removePhotoRequested ? null : $oldPhoto);

                try {
                    $statement = Database::getConnection()->prepare(
                        'UPDATE users SET phone = ?, profile_photo = ? WHERE id = ?'
                    );
                    $statement->execute([
                        $values['phone'] === '' ? null : $values['phone'],
                        $profilePhoto,
                        $account->getId(),
                    ]);
                } catch (PDOException $exception) {
                    if ($newPhoto !== null) {
                        $this->deleteProfilePhoto($newPhoto);
                    }
                    throw $exception;
                }

                if ($oldPhoto !== null && $oldPhoto !== $profilePhoto) {
                    $this->deleteProfilePhoto($oldPhoto);
                }
                flash('Ton profil a été mis à jour.');
                redirect('dashboard');
            }

            http_response_code(422);
        }

        $section = 'profile';
        render('front/profile', 'Mon espace', compact('account', 'section', 'values', 'errors', 'removePhotoRequested'));
    }

    public function profilePhoto(): void
    {
        $account = $this->requireAuth();
        $filename = $account->getProfilePhoto();
        if ($filename === null || preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/D', $filename) !== 1) {
            errorPage(404, 'Aucune photo de profil n’est disponible.');
        }

        $extensions = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $path = $this->profilePhotoDirectory() . DIRECTORY_SEPARATOR . $filename;
        if (!is_file($path)) {
            errorPage(404, 'Aucune photo de profil n’est disponible.');
        }
        if (!is_readable($path)) {
            error_log('Unable to read profile photo: ' . $path);
            errorPage(503, 'La photo de profil est temporairement indisponible.');
        }

        header('Content-Type: ' . $extensions[$extension]);
        header('Content-Disposition: inline; filename="profile-photo.' . $extension . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }

    public function logout(): void
    {
        $this->requireAuth();
        requireCsrf();
        endSession();
        redirect('login');
    }

    private function currentUser(): ?UserModel
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        $user = $this->findUser($_SESSION['user_id']);
        if ($user === null) {
            $_SESSION = [];
            session_regenerate_id(true);
            return null;
        }
        $_SESSION['role'] = $user->getRole();
        return $user;
    }

    public function requireAuth(): UserModel
    {
        $user = $this->currentUser();
        if ($user === null) {
            redirect('login');
        }
        return $user;
    }

    public function requireRole(string $role): UserModel
    {
        $user = $this->requireAuth();
        if ($_SESSION['role'] !== $role) {
            errorPage(403, 'Cette page est réservée aux administrateurs.');
        }
        return $user;
    }

    private function redirectIfSignedIn(): void
    {
        $account = $this->currentUser();
        if ($account !== null) {
            redirect($account->getRole() === 'admin' ? 'admin' : 'dashboard');
        }
    }

    private function findUser(int $id): ?UserModel
    {
        $statement = Database::getConnection()->prepare('SELECT ' . self::USER_FIELDS . ' FROM users WHERE id = ?');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ? UserModel::fromArray($row) : null;
    }

    private function requestedUser(): UserModel
    {
        $id = filter_var(requestText($_GET, 'id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $user = $id === false ? null : $this->findUser($id);
        if ($user === null) {
            errorPage(404, 'Cet utilisateur est introuvable.');
        }
        return $user;
    }

    public function users(): void
    {
        $account = $this->requireRole('admin');
        $query = searchQuery();
        $sql = 'SELECT ' . self::USER_FIELDS . ' FROM users';
        $parameters = [];

        if ($query !== '') {
            $sql .= ' WHERE LOCATE(?, username) > 0 OR LOCATE(?, email) > 0';
            $parameters = [$query, $query];
        }
        $statement = Database::getConnection()->prepare($sql . ' ORDER BY id DESC');
        $statement->execute($parameters);
        $users = array_map([UserModel::class, 'fromArray'], $statement->fetchAll());
        $section = 'users';

        render('admin/users', 'Gestion des utilisateurs', compact('account', 'query', 'users', 'section'), 'admin');
    }

    public function saveUser(string $method, bool $editing): void
    {
        $account = $this->requireRole('admin');
        $target = $editing ? $this->requestedUser() : null;
        $ownAccount = $target !== null && $target->getId() === $account->getId();
        $values = [
            'username' => $target ? $target->getUsername() : '',
            'email' => $target ? $target->getEmail() : '',
            'role' => $target ? $target->getRole() : 'user',
        ];
        $errors = [];

        if ($method === 'POST') {
            requireCsrf();
            [$values, $password, $errors] = $this->validateUser(true, !$editing);
            if ($ownAccount && $values['role'] !== 'admin') {
                $errors['role'] = 'Tu dois conserver le rôle administrateur sur ton compte.';
                $values['role'] = 'admin';
            }

            if ($errors === []) {
                $errors = $this->asAdmin($account, $target, function () use ($values, $password, $target) {
                    return $this->writeUser($values, $password, $target?->getId());
                });
                if ($errors === []) {
                    flash($editing ? 'Utilisateur modifié.' : 'Utilisateur ajouté.');
                    redirect('users');
                }
            }
            http_response_code(422);
        }

        $section = 'users';
        $title = $editing ? 'Modifier un utilisateur' : 'Ajouter un utilisateur';
        render('admin/user-form', $title, compact('account', 'target', 'values', 'errors', 'section', 'ownAccount'), 'admin');
    }

    public function deleteUser(string $method): void
    {
        $account = $this->requireRole('admin');
        $target = $this->requestedUser();
        if ($target->getId() === $account->getId()) {
            errorPage(403, 'Tu ne peux pas supprimer ton propre compte administrateur.');
        }

        if ($method === 'POST') {
            requireCsrf();
            $this->asAdmin($account, $target, function () use ($target) {
                $database = Database::getConnection();
                $statement = $database->prepare(
                    'DELETE conversations FROM conversations
                    INNER JOIN conversation_participants
                        ON conversation_participants.conversation_id = conversations.id
                    WHERE conversation_participants.user_id = ?'
                );
                $statement->execute([$target->getId()]);
                $statement = $database->prepare('DELETE FROM users WHERE id = ?');
                $statement->execute([$target->getId()]);
            });
            flash('Utilisateur supprimé.');
            redirect('users');
        }

        $section = 'users';
        render('admin/user-delete', 'Supprimer un utilisateur', compact('account', 'target', 'section'), 'admin');
    }

    private function asAdmin(UserModel $account, ?UserModel $target, callable $operation): mixed
    {
        $database = Database::getConnection();
        $database->beginTransaction();

        try {
            // Sérialise les modifications de comptes administrateurs et vérifie le rôle actuel.
            $statement = $database->prepare('SELECT id FROM users WHERE role = ? ORDER BY id FOR UPDATE');
            $statement->execute(['admin']);
            $adminIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
            if (!in_array($account->getId(), $adminIds, true)) {
                $database->rollBack();
                errorPage(403, 'Ce compte ne dispose plus du rôle administrateur.');
            }
            if ($target !== null) {
                $statement = $database->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
                $statement->execute([$target->getId()]);
                if ($statement->fetchColumn() === false) {
                    $database->rollBack();
                    errorPage(404, 'Cet utilisateur est introuvable.');
                }
            }

            $result = $operation();
            $database->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $exception;
        }
    }

    private function validateUser(bool $admin, bool $requiresPassword): array
    {
        $values = [
            'username' => trim(requestText($_POST, 'username')),
            'email' => trim(requestText($_POST, 'email')),
        ];
        $password = requestText($_POST, 'password');
        $errors = [];

        if (preg_match('/^[a-zA-Z0-9_-]{3,50}$/D', $values['username']) !== 1) {
            $errors['username'] = 'Entre de 3 à 50 caractères (lettres sans accents, chiffres, tirets ou tirets bas).';
        }
        if (!$this->validEmail($values['email'])) {
            $errors['email'] = 'Entre une adresse e-mail valide.';
        }
        if (($requiresPassword || $password !== '') && !$this->validPassword($password, 8)) {
            $errors['password'] = 'Choisis un mot de passe d’au moins 8 caractères et de 72 octets maximum.';
        }
        if ($admin) {
            $values['role'] = requestText($_POST, 'role');
            if (!in_array($values['role'], ['user', 'admin'], true)) {
                $errors['role'] = 'Sélectionne un rôle valide.';
            }
        }
        return [$values, $password, $errors];
    }

    private function validPhone(string $phone): bool
    {
        if ($phone === '') {
            return true;
        }

        $digitCount = preg_match_all('/[0-9]/', $phone);
        return strlen($phone) <= 30
            && preg_match('/\A\+?[0-9\s().-]+\z/D', $phone) === 1
            && $digitCount >= 6
            && $digitCount <= 20;
    }

    private function storeProfilePhoto(array $upload): array
    {
        $error = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
        if (!is_int($error)) {
            return [null, 'Le fichier envoyé est invalide.'];
        }
        if ($error === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }
        if ($error !== UPLOAD_ERR_OK) {
            return [null, $error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE
                ? 'La photo ne doit pas dépasser 2 Mo.'
                : 'La photo n’a pas pu être téléversée. Réessaie.'];
        }

        $temporaryPath = $upload['tmp_name'] ?? '';
        if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
            return [null, 'Le fichier envoyé est invalide.'];
        }

        $size = filesize($temporaryPath);
        if ($size === false || $size < 1 || $size > self::PROFILE_PHOTO_MAX_BYTES) {
            return [null, 'La photo ne doit pas dépasser 2 Mo.'];
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
        $image = @getimagesize($temporaryPath);
        $supportedTypes = [
            IMAGETYPE_JPEG => ['mime' => 'image/jpeg', 'extension' => 'jpg'],
            IMAGETYPE_PNG => ['mime' => 'image/png', 'extension' => 'png'],
            IMAGETYPE_WEBP => ['mime' => 'image/webp', 'extension' => 'webp'],
        ];
        if (
            !is_array($image)
            || !isset($supportedTypes[$image[2] ?? null])
            || $mime !== $supportedTypes[$image[2]]['mime']
        ) {
            return [null, 'Choisis une image JPEG, PNG ou WebP valide.'];
        }

        $width = (int) $image[0];
        $height = (int) $image[1];
        if (
            $width < 1
            || $height < 1
            || $width > 8000
            || $height > 8000
            || $width * $height > self::PROFILE_PHOTO_MAX_PIXELS
        ) {
            return [null, 'Les dimensions de la photo sont trop grandes.'];
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $supportedTypes[$image[2]]['extension'];
        $destination = $this->profilePhotoDirectory() . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($temporaryPath, $destination)) {
            return [null, 'La photo n’a pas pu être enregistrée. Réessaie.'];
        }

        return [$filename, null];
    }

    private function profilePhotoDirectory(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile';
    }

    private function deleteProfilePhoto(string $filename): void
    {
        if (preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/D', $filename) !== 1) {
            error_log('Refusing to delete invalid profile photo filename.');
            return;
        }

        $path = $this->profilePhotoDirectory() . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path) && !unlink($path)) {
            error_log('Unable to remove profile photo: ' . $path);
        }
    }

    private function validEmail(string $email): bool
    {
        return strlen($email) <= 150 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validPassword(string $password, int $minimum): bool
    {
        return strlen($password) <= 72
            && preg_match('/^.{' . $minimum . ',}$/usD', $password) === 1
            && !str_contains($password, "\0");
    }

    private function writeUser(array $values, string $password, ?int $id = null): array
    {
        $database = Database::getConnection();
        if ($password !== '') {
            $values['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($id === null) {
            $sql = 'INSERT INTO users (username, email, role, password) VALUES (:username, :email, :role, :password)';
        } else {
            $sql = 'UPDATE users SET username = :username, email = :email, role = :role';
            if (isset($values['password'])) {
                $sql .= ', password = :password';
            }
            $sql .= ' WHERE id = :id';
            $values['id'] = $id;
        }

        try {
            $database->prepare($sql)->execute($values);
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                return ['general' => 'Ce nom ou cette adresse e-mail est déjà utilisé.'];
            }
            throw $exception;
        }

        return [];
    }
}
