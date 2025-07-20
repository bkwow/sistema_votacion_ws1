<?php
// ---- ARCHIVO: includes/header.php (VERSIÓN COMPLETA Y FINAL) ----

// Asegurarse de que la sesión esté iniciada para acceder a las variables de sesión.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Panel'; ?> - <?php echo PROJECT_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Estilo para el enlace activo del menú */
        .active-nav-link {
            background-color: #111827; /* bg-gray-900 */
            color: #ffffff; /* text-white */
        }
        /* Estilo para enlaces inactivos del menú */
        .inactive-nav-link {
            color: #d1d5db; /* text-gray-300 */
            transition: background-color 0.2s, color 0.2s;
        }
        .inactive-nav-link:hover {
            background-color: #374151; /* hover:bg-gray-700 */
            color: #ffffff; /* hover:text-white */
        }
    </style>
</head>
<body class="h-full">
<div class="min-h-full">
  <nav class="bg-gray-800">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 items-center justify-between">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-8 w-8 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="hidden md:block">
            <div class="ml-10 flex items-baseline space-x-4">
              <?php
                // Determinar la página actual para el estilo activo
                $current_page = basename($_SERVER['PHP_SELF']);
                $user_role = $_SESSION['user_role'] ?? 'voter';

                // Definir los enlaces del menú basados en el rol del usuario
                $nav_links = [];
                if ($user_role === 'superadmin') {
                    $nav_links = [
                        'superadmin_dashboard.php' => 'Gestión Usuarios',
                        'admin_dashboard.php' => 'Gestión Votaciones',
                        'reports.php' => 'Reportes',
                        'livestream_settings.php' => 'Ajustes Transmisión',
                        'chat_settings.php' => 'Ajustes Chat',
                        'chart_settings.php' => 'Ajustes Gráficos'
                    ];
                } elseif ($user_role === 'admin') {
                    $nav_links = [
                        'admin_dashboard.php' => 'Gestión Votaciones',
                        'reports.php' => 'Reportes',
                        'livestream_settings.php' => 'Ajustes Transmisión',
                        'chat_settings.php' => 'Ajustes Chat',
                        'chart_settings.php' => 'Ajustes Gráficos'
                    ];
                } else { // Rol de 'voter'
                    $nav_links = [
                        'voter_dashboard.php' => 'Panel de Votante'
                    ];
                }

                // Renderizar los enlaces del menú
                foreach ($nav_links as $url => $text) {
                    $is_active = ($current_page === $url);
                    // Aplicar la clase CSS correcta para resaltar el enlace activo
                    $class = $is_active ? 'active-nav-link' : 'inactive-nav-link';
                    echo "<a href='{$url}' class='{$class} rounded-md px-3 py-2 text-sm font-medium'>{$text}</a>";
                }
              ?>
            </div>
          </div>
        </div>
        <div class="hidden md:block">
          <div class="ml-4 flex items-center md:ml-6">
            <span class="text-gray-400 text-sm mr-4">Hola, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="logout.php" class="rounded-full bg-gray-700 p-1 text-gray-300 hover:text-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800" title="Cerrar Sesión">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                </svg>
            </a>
          </div>
        </div>
        <!-- Menú para móviles (hamburger) se podría añadir aquí en el futuro -->
      </div>
    </div>
  </nav>

  <header class="bg-white shadow">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900"><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Panel'; ?></h1>
    </div>
  </header>
  <main>
    <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
      <!-- El contenido específico de cada página se insertará aquí -->
