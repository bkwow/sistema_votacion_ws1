<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Asistencia</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome CDN for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts (Poppins) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Custom styles */
        body {
            font-family: 'Poppins', sans-serif;
        }
        /* Style for the scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .content-section {
            display: none;
        }
        .content-section.active {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen bg-gray-200">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 text-white flex-shrink-0">
            <div class="p-6 text-2xl font-semibold text-center">
                <i class="fas fa-user-check mr-2"></i>
                <span>Asistencia</span>
            </div>
            <nav class="mt-8">
                <a href="#dashboard" class="nav-link flex items-center py-3 px-6 text-gray-300 hover:bg-gray-700 hover:text-white bg-gray-700">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span class="ml-4">Dashboard</span>
                </a>
                <a href="#empleados" class="nav-link flex items-center py-3 px-6 text-gray-300 hover:bg-gray-700 hover:text-white">
                    <i class="fas fa-users w-6"></i>
                    <span class="ml-4">Empleados</span>
                </a>
                <a href="#asistencia" class="nav-link flex items-center py-3 px-6 text-gray-300 hover:bg-gray-700 hover:text-white">
                    <i class="fas fa-calendar-check w-6"></i>
                    <span class="ml-4">Asistencia</span>
                </a>
                <a href="#reportes" class="nav-link flex items-center py-3 px-6 text-gray-300 hover:bg-gray-700 hover:text-white">
                    <i class="fas fa-file-alt w-6"></i>
                    <span class="ml-4">Reportes</span>
                </a>
                <a href="#usuarios" class="nav-link flex items-center py-3 px-6 text-gray-300 hover:bg-gray-700 hover:text-white">
                    <i class="fas fa-user-shield w-6"></i>
                    <span class="ml-4">Usuarios</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <div class="flex items-center">
                    <button id="sidebar-toggle" class="text-gray-500 focus:outline-none lg:hidden">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                    <h1 class="text-2xl font-semibold text-gray-800 ml-4" id="section-title">Dashboard</h1>
                </div>
                <div class="relative">
                    <button id="profile-button" class="flex items-center focus:outline-none">
                        <span class="mr-2 hidden md:inline">Admin</span>
                        <img src="https://placehold.co/40x40/E2E8F0/4A5568?text=A" alt="Avatar de usuario" class="w-10 h-10 rounded-full">
                    </button>
                    <!-- Dropdown -->
                    <div id="profile-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-20">
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Mi Perfil</a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Configuración</a>
                        <div class="border-t border-gray-100"></div>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Cerrar Sesión</a>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                
                <!-- Dashboard Section -->
                <div id="dashboard" class="content-section active">
                    <h2 class="text-3xl font-bold mb-6 text-gray-800">Resumen General</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Empleados</p>
                                <p class="text-3xl font-bold text-gray-800">125</p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-users fa-2x text-blue-500"></i>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Presentes Hoy</p>
                                <p class="text-3xl font-bold text-gray-800">110</p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-user-check fa-2x text-green-500"></i>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Ausentes</p>
                                <p class="text-3xl font-bold text-gray-800">10</p>
                            </div>
                            <div class="bg-red-100 p-3 rounded-full">
                                <i class="fas fa-user-times fa-2x text-red-500"></i>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Con Permiso</p>
                                <p class="text-3xl font-bold text-gray-800">5</p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-user-clock fa-2x text-yellow-500"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empleados Section -->
                <div id="empleados" class="content-section">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-2xl font-semibold">Lista de Empleados</h2>
                            <button id="add-employee-btn" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                                <i class="fas fa-plus mr-2"></i>
                                Agregar Empleado
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-full bg-white">
                                <thead class="bg-gray-200 text-gray-600">
                                    <tr>
                                        <th class="py-3 px-4 text-left">ID</th>
                                        <th class="py-3 px-4 text-left">Nombre</th>
                                        <th class="py-3 px-4 text-left">Cargo</th>
                                        <th class="py-3 px-4 text-left">Departamento</th>
                                        <th class="py-3 px-4 text-left">Estado</th>
                                        <th class="py-3 px-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700">
                                    <!-- Sample Rows -->
                                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                                        <td class="py-3 px-4">EMP001</td>
                                        <td class="py-3 px-4">Juan Pérez</td>
                                        <td class="py-3 px-4">Desarrollador Web</td>
                                        <td class="py-3 px-4">Tecnología</td>
                                        <td class="py-3 px-4"><span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs">Activo</span></td>
                                        <td class="py-3 px-4 text-center">
                                            <button class="text-blue-500 hover:text-blue-700 mr-2"><i class="fas fa-edit"></i></button>
                                            <button class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                                        <td class="py-3 px-4">EMP002</td>
                                        <td class="py-3 px-4">María García</td>
                                        <td class="py-3 px-4">Diseñadora UX</td>
                                        <td class="py-3 px-4">Diseño</td>
                                        <td class="py-3 px-4"><span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs">Activo</span></td>
                                        <td class="py-3 px-4 text-center">
                                            <button class="text-blue-500 hover:text-blue-700 mr-2"><i class="fas fa-edit"></i></button>
                                            <button class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                                        <td class="py-3 px-4">EMP003</td>
                                        <td class="py-3 px-4">Carlos Rodriguez</td>
                                        <td class="py-3 px-4">Gerente de Proyecto</td>
                                        <td class="py-3 px-4">Administración</td>
                                        <td class="py-3 px-4"><span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs">Inactivo</span></td>
                                        <td class="py-3 px-4 text-center">
                                            <button class="text-blue-500 hover:text-blue-700 mr-2"><i class="fas fa-edit"></i></button>
                                            <button class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Other Sections (Asistencia, Reportes, Usuarios) -->
                <div id="asistencia" class="content-section">
                    <h2 class="text-3xl font-bold mb-6 text-gray-800">Registro de Asistencia</h2>
                    <div class="bg-white p-6 rounded-lg shadow-md">Contenido de Asistencia aquí...</div>
                </div>
                <div id="reportes" class="content-section">
                    <h2 class="text-3xl font-bold mb-6 text-gray-800">Generación de Reportes</h2>
                    <div class="bg-white p-6 rounded-lg shadow-md">Contenido de Reportes aquí...</div>
                </div>
                <div id="usuarios" class="content-section">
                    <h2 class="text-3xl font-bold mb-6 text-gray-800">Gestión de Usuarios</h2>
                    <div class="bg-white p-6 rounded-lg shadow-md">Contenido de Usuarios aquí...</div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div id="employee-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-30 hidden">
        <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">Agregar Nuevo Empleado</h2>
                <button id="close-modal-btn" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <form>
                <div class="mb-4">
                    <label for="nombre" class="block text-gray-700 text-sm font-bold mb-2">Nombre Completo</label>
                    <input type="text" id="nombre" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Ej: Juan Pérez">
                </div>
                <div class="mb-4">
                    <label for="cargo" class="block text-gray-700 text-sm font-bold mb-2">Cargo</label>
                    <input type="text" id="cargo" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Ej: Desarrollador Web">
                </div>
                <div class="mb-6">
                    <label for="departamento" class="block text-gray-700 text-sm font-bold mb-2">Departamento</label>
                    <select id="departamento" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option>Tecnología</option>
                        <option>Diseño</option>
                        <option>Administración</option>
                        <option>Recursos Humanos</option>
                    </select>
                </div>
                <div class="flex items-center justify-end">
                    <button type="button" id="cancel-modal-btn" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg mr-2">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">
                        Guardar Empleado
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const navLinks = document.querySelectorAll('.nav-link');
            const contentSections = document.querySelectorAll('.content-section');
            const sectionTitle = document.getElementById('section-title');
            const profileButton = document.getElementById('profile-button');
            const profileDropdown = document.getElementById('profile-dropdown');
            const sidebar = document.querySelector('aside');
            const sidebarToggle = document.getElementById('sidebar-toggle');

            // --- Navigation Logic ---
            navLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    
                    // Update active link style
                    navLinks.forEach(l => l.classList.remove('bg-gray-700'));
                    this.classList.add('bg-gray-700');

                    // Show correct content section
                    const targetId = this.getAttribute('href').substring(1);
                    contentSections.forEach(section => {
                        section.classList.remove('active');
                        if (section.id === targetId) {
                            section.classList.add('active');
                        }
                    });

                    // Update header title
                    sectionTitle.textContent = this.querySelector('span').textContent;
                });
            });

            // --- Profile Dropdown Logic ---
            profileButton.addEventListener('click', function () {
                profileDropdown.classList.toggle('hidden');
            });

            // Close dropdown if clicked outside
            window.addEventListener('click', function(e) {
                if (!profileButton.contains(e.target) && !profileDropdown.contains(e.target)) {
                    profileDropdown.classList.add('hidden');
                }
            });

            // --- Sidebar Toggle for Mobile ---
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
            });

            // --- Modal Logic ---
            const modal = document.getElementById('employee-modal');
            const addEmployeeBtn = document.getElementById('add-employee-btn');
            const closeModalBtn = document.getElementById('close-modal-btn');
            const cancelModalBtn = document.getElementById('cancel-modal-btn');

            addEmployeeBtn.addEventListener('click', () => modal.classList.remove('hidden'));
            closeModalBtn.addEventListener('click', () => modal.classList.add('hidden'));
            cancelModalBtn.addEventListener('click', () => modal.classList.add('hidden'));
            
            // Close modal on outside click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
