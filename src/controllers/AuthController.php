<?php
/**
 * TEXTUM - AuthController
 * Maneja login y logout
 */
class AuthController {

    public function loginPage(): void {
        if (Auth::check()) {
            header('Location: ' . BASE_URL . '/index.php?page=dashboard');
            exit;
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        require VIEW_PATH . '/auth/login.php';
    }

    public function loginPost(): void {
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (Auth::login($email, $password)) {
            header('Location: ' . BASE_URL . '/index.php?page=dashboard');
            exit;
        }

        $_SESSION['login_error'] = 'Email o contraseña incorrectos.';
        header('Location: ' . BASE_URL . '/index.php?page=login');
        exit;
    }

    public function logout(): void {
        Auth::logout();
        header('Location: ' . BASE_URL . '/index.php?page=login');
        exit;
    }
}
