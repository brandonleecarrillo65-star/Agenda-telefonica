<?php
// 1. CONEXIÓN CON TU ARCHIVO DE BASE DE DATOS
require_once 'conexion.php';

// Variables para controlar cuando se edita un registro
$id_edit = '';
$nombre_edit = '';
$apellido_edit = '';
$telefono_edit = '';
$pais_edit = '';
$oficio_edit = '';
$es_edicion = false;

// 2. PROCESAR ELIMINACIÓN
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = $pdo->prepare("DELETE FROM contactos WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php");
    exit;
}

// 3. CARGAR DATOS EN EL FORMULARIO PARA EDITAR
if (isset($_GET['editar'])) {
    $id = intval($_GET['editar']);
    $stmt = $pdo->prepare("SELECT * FROM contactos WHERE id = ?");
    $stmt->execute([$id]);
    $contacto = $stmt->fetch();
    if ($contacto) {
        $id_edit = $contacto['id'];
        $nombre_edit = $contacto['nombre'];
        $apellido_edit = $contacto['apellido'];
        $telefono_edit = $contacto['telefono'];
        $pais_edit = $contacto['pais'];
        $oficio_edit = $contacto['oficio'];
        $es_edicion = true;
    }
}

// 4. GUARDAR / ACTUALIZAR DATOS EN LA BASE DE DATOS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $telefono = trim($_POST['telefono']);
    $pais = trim($_POST['pais']);
    $oficio = trim($_POST['oficio']);
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if (!empty($nombre) && !empty($apellido) && !empty($telefono)) {
        if ($id > 0) {
            // Actualiza en MySQL
            $stmt = $pdo->prepare("UPDATE contactos SET nombre=?, apellido=?, telefono=?, pais=?, oficio=? WHERE id=?");
            $stmt->execute([$nombre, $apellido, $telefono, $pais, $oficio, $id]);
        } else {
            // Inserta en MySQL
            $stmt = $pdo->prepare("INSERT INTO contactos (nombre, apellido, telefono, pais, oficio) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $apellido, $telefono, $pais, $oficio]);
        }
        header("Location: index.php");
        exit;
    }
}

// 5. OBTENER CONTACTOS DESDE LA BASE DE DATOS
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
if ($busqueda !== '') {
    $stmt = $pdo->prepare("SELECT * FROM contactos WHERE nombre LIKE ? OR apellido LIKE ? OR telefono LIKE ? OR oficio LIKE ? OR pais LIKE ? ORDER BY id DESC");
    $term = "%$busqueda%";
    $stmt->execute([$term, $term, $term, $term, $term]);
} else {
    $stmt = $pdo->query("SELECT * FROM contactos ORDER BY id DESC");
}
$contactos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Telefónica Digital</title>
    <!-- Vinculación a tu CSS externo -->
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

    <div class="container">
        <header class="app-header">
            <h1>📖 Agenda Telefónica</h1>
            <p>Sistema de gestión de contactos</p>
        </header>

        <main class="grid-layout">
            <!-- FORMULARIO DE ENTRADA CONECTADO CON PHP/MYSQL -->
            <section class="card form-card">
                <h2><?= $es_edicion ? '✏️ Editar Contacto' : '➕ Nuevo Contacto' ?></h2>
                <form action="index.php" method="POST" id="contact-form">
                    <input type="hidden" name="id" value="<?= $id_edit ?>">

                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre_edit) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="apellido">Apellido</label>
                        <input type="text" id="apellido" name="apellido" value="<?= htmlspecialchars($apellido_edit) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" value="<?= htmlspecialchars($telefono_edit) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="pais">País</label>
                        <input type="text" id="pais" name="pais" value="<?= htmlspecialchars($pais_edit) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="oficio">Oficio / Profesión</label>
                        <input type="text" id="oficio" name="oficio" value="<?= htmlspecialchars($oficio_edit) ?>" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            💾 <?= $es_edicion ? 'Actualizar' : 'Guardar Contacto' ?>
                        </button>
                        <?php if ($es_edicion): ?>
                            <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <!-- TABLA DE RESULTADOS CONECTADA A LA BASE DE DATOS -->
            <section class="card list-card">
                <div class="list-header">
                    <h2>📋 Lista de Contactos (<?= count($contactos) ?>)</h2>
                    
                    <form action="index.php" method="GET" class="search-box">
                        <input type="text" name="buscar" placeholder="Buscar por nombre, teléfono, oficio..." value="<?= htmlspecialchars($busqueda) ?>">
                        <button type="submit" class="btn btn-search">🔍 Buscar</button>
                        <?php if ($busqueda !== ''): ?>
                            <a href="index.php" class="btn btn-secondary btn-sm">Limpiar</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre Completo</th>
                                <th>Teléfono</th>
                                <th>País</th>
                                <th>Oficio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($contactos) > 0): ?>
                                <?php foreach ($contactos as $c): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?></strong></td>
                                        <td>📞 <?= htmlspecialchars($c['telefono']) ?></td>
                                        <td>🌍 <?= htmlspecialchars($c['pais']) ?></td>
                                        <td>💼 <?= htmlspecialchars($c['oficio']) ?></td>
                                        <td class="actions-cell">
                                            <a href="index.php?editar=<?= $c['id'] ?>" class="btn-action edit" title="Editar">✏️</a>
                                            <a href="index.php?eliminar=<?= $c['id'] ?>" class="btn-action delete" onclick="return confirm('¿Seguro que deseas eliminar este contacto?');" title="Eliminar">🗑️</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="empty-msg">No se encontraron contactos registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

</body>
</html>