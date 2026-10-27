<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/config/mercadopago.php';

$amount = trim((string) ($_POST['amount'] ?? '5.00'));
$donorName = trim((string) ($_POST['donor_name'] ?? ''));
$donorEmail = trim((string) ($_POST['donor_email'] ?? ''));
$method = (string) ($_POST['method'] ?? 'card');
$context = trim((string) ($_POST['context'] ?? $_GET['context'] ?? ''));
$errors = [];
$receipt = $_SESSION['simulated_donation'] ?? null;
unset($_SESSION['simulated_donation']);
$mercadoPagoConfigured = !MERCADOPAGO_SIMULATION_MODE && mercado_pago_is_configured();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $amountValue = filter_var($amount, FILTER_VALIDATE_FLOAT);
    $allowedMethods = ['card', 'yape', 'paypal'];

    if ($amountValue === false || $amountValue < 1 || $amountValue > 1000) {
        $errors[] = 'El monto debe estar entre S/ 1.00 y S/ 1,000.00.';
    }
    if (strlen($donorName) < 2) {
        $errors[] = 'Escribe tu nombre para continuar.';
    }
    if ($donorEmail !== '' && !filter_var($donorEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El correo ingresado no es valido.';
    }
    if (!in_array($method, $allowedMethods, true)) {
        $errors[] = 'Selecciona un metodo de donacion valido.';
    }

    if (!$errors && $mercadoPagoConfigured) {
        $externalReference = 'DN-DONATION-' . strtoupper(bin2hex(random_bytes(6)));
        $backUrl = MERCADOPAGO_SITE_URL . '/donation-result.php';
        $payload = [
            'items' => [[
                'title' => 'Donacion DarkNovels',
                'description' => $context !== '' ? $context : 'Apoyo a la lectura libre',
                'quantity' => 1,
                'currency_id' => 'PEN',
                'unit_price' => (float) $amountValue,
            ]],
            'external_reference' => $externalReference,
            'back_urls' => [
                'success' => $backUrl,
                'pending' => $backUrl,
                'failure' => $backUrl,
            ],
            'auto_return' => 'approved',
            'notification_url' => MERCADOPAGO_SITE_URL . '/mercadopago-webhook.php',
        ];
        if ($donorEmail !== '') {
            $payload['payer'] = ['name' => $donorName, 'email' => $donorEmail];
        }

        $response = mercado_pago_api_request('POST', '/checkout/preferences', $payload);
        $checkoutUrl = MERCADOPAGO_TEST_MODE
            ? ($response['data']['sandbox_init_point'] ?? $response['data']['init_point'] ?? '')
            : ($response['data']['init_point'] ?? '');

        if ($response['status'] >= 200 && $response['status'] < 300 && $checkoutUrl !== '') {
            header('Location: ' . $checkoutUrl);
            exit;
        }

        error_log('Mercado Pago preference error: ' . json_encode($response['data']));
        $errors[] = 'No se pudo iniciar el pago con Mercado Pago. Revisa tus credenciales.';
    } elseif (!$errors) {
        $_SESSION['simulated_donation'] = [
            'reference' => 'SIM-' . strtoupper(bin2hex(random_bytes(4))),
            'amount' => number_format((float) $amountValue, 2, '.', ''),
            'donor_name' => $donorName,
            'method' => $method,
            'context' => $context,
            'created_at' => date('d/m/Y H:i'),
        ];
        redirect('donations.php');
    }
}

$methodLabels = [
    'card' => 'Tarjeta de prueba',
    'yape' => 'Yape simulado',
    'paypal' => 'PayPal simulado',
];

$pageTitle = 'Donaciones';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero compact">
    <div class="container">
        <p class="eyebrow">Apoya la lectura libre</p>
        <h1>Donaciones</h1>
        <p class="lead mb-0">Tu apoyo ayuda a mantener DarkNovels abierto para todos.</p>
    </div>
</section>

<section class="section-pad pt-4">
    <div class="container content-grid donation-layout">
        <div class="donation-card">
            <?php if ($receipt): ?>
                <div class="donation-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <p class="eyebrow mb-2">Donacion simulada aprobada</p>
                    <h2>Gracias, <?= e($receipt['donor_name']) ?>.</h2>
                    <p>Tu donacion de <strong>S/ <?= e($receipt['amount']) ?></strong> fue registrada solo como prueba.</p>
                    <p class="mb-2">Referencia: <strong><?= e($receipt['reference']) ?></strong></p>
                    <small class="text-secondary">Metodo: <?= e($methodLabels[$receipt['method']] ?? 'Simulado') ?> · <?= e($receipt['created_at']) ?></small>
                    <a class="btn btn-danger mt-4" href="<?= url('pages/library.php') ?>"><i class="bi bi-book"></i> Volver a la biblioteca</a>
                </div>
            <?php else: ?>
                <div class="section-head">
                    <div>
                        <p class="eyebrow"><?= $mercadoPagoConfigured ? 'Mercado Pago' : 'Modo de prueba' ?></p>
                        <h2>Haz una donacion</h2>
                    </div>
                    <i class="bi bi-heart-fill donation-icon"></i>
                </div>
                <div class="demo-notice"><i class="bi bi-info-circle"></i><span><?= $mercadoPagoConfigured ? 'Seras redirigido a Mercado Pago. El pago se procesara en su entorno seguro.' : 'No se realiza ningun cobro real. Esta pasarela funciona como simulacion gratuita.' ?></span></div>

                <?php if ($errors): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="donation-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="context" value="<?= e($context) ?>">
                    <label class="form-label" for="donationAmount">Monto (S/)</label>
                    <div class="donation-amount-row">
                        <button class="btn btn-outline-light" type="button" data-donation-amount="3">S/ 3</button>
                        <button class="btn btn-outline-light" type="button" data-donation-amount="5">S/ 5</button>
                        <button class="btn btn-outline-light" type="button" data-donation-amount="10">S/ 10</button>
                        <input class="form-control" id="donationAmount" type="number" name="amount" min="1" max="1000" step="0.01" value="<?= e($amount) ?>" required>
                    </div>
                    <label class="form-label mt-3" for="donorName">Nombre</label>
                    <input class="form-control" id="donorName" type="text" name="donor_name" value="<?= e($donorName) ?>" maxlength="100" required>
                    <label class="form-label mt-3" for="donorEmail">Correo (opcional)</label>
                    <input class="form-control" id="donorEmail" type="email" name="donor_email" value="<?= e($donorEmail) ?>" maxlength="150">
                    <?php if (!$mercadoPagoConfigured): ?>
                    <fieldset class="mt-3">
                        <legend class="form-label">Metodo simulado</legend>
                        <div class="donation-methods">
                            <label class="donation-method"><input type="radio" name="method" value="card" <?= $method === 'card' ? 'checked' : '' ?>> <i class="bi bi-credit-card"></i> Tarjeta</label>
                            <label class="donation-method"><input type="radio" name="method" value="yape" <?= $method === 'yape' ? 'checked' : '' ?>> <i class="bi bi-phone"></i> Yape</label>
                            <label class="donation-method"><input type="radio" name="method" value="paypal" <?= $method === 'paypal' ? 'checked' : '' ?>> <i class="bi bi-wallet2"></i> PayPal</label>
                        </div>
                    </fieldset>
                    <?php endif; ?>
                    <button class="btn btn-danger btn-lg w-100 mt-4" type="submit"><i class="bi bi-heart-fill"></i> <?= $mercadoPagoConfigured ? 'Continuar con Mercado Pago' : 'Simular donacion' ?></button>
                </form>
            <?php endif; ?>
        </div>
        <aside class="side-panel">
            <i class="bi bi-shield-check donation-side-icon"></i>
            <h3><?= $mercadoPagoConfigured ? 'Pago seguro' : 'Modo gratuito' ?></h3>
            <p><?= $mercadoPagoConfigured ? 'Mercado Pago procesa los datos del pago. DarkNovels no almacena tarjetas.' : 'La simulacion no usa una pasarela externa, no solicita datos bancarios reales y no genera cargos.' ?></p>
            <?php if ($context): ?><p class="mb-0 text-secondary">Gracias por apoyar despues de leer: <strong><?= e($context) ?></strong></p><?php endif; ?>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
