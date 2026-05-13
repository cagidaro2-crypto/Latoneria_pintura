
<?php
session_start();

if(!isset($_SESSION['usuario'])) {
    header("Location: index.php");
}

include '../views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 bg-dark text-white vh-100 p-3">
            <h4>SystemTaller</h4>
            <hr>

            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link text-white" href="#">Clientes</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="#">Vehículos</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="#">Inventario</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="#">Facturación</a></li>
            </ul>
        </div>

        <div class="col-md-10 p-4">
            <h1>Bienvenido Administrador</h1>

            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card shadow">
                        <div class="card-body">
                            <h5>Clientes</h5>
                            <h2>25</h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow">
                        <div class="card-body">
                            <h5>Vehículos</h5>
                            <h2>14</h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow">
                        <div class="card-body">
                            <h5>Órdenes</h5>
                            <h2>7</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../views/layouts/footer.php'; ?>
