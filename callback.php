<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

function respond(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$bookingId = (int) ($input['booking_id'] ?? $_GET['booking_id'] ?? 0);
$transactionId = trim((string) ($input['transaction_id'] ?? $_GET['transaction_id'] ?? ''));
$status = strtolower(trim((string) ($input['status'] ?? $_GET['status'] ?? '')));
$metode = trim((string) ($input['metode'] ?? $_GET['metode'] ?? 'gateway'));
$token = trim((string) ($input['token'] ?? $_GET['token'] ?? $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? ''));

if ($token === '' || !hash_equals(PAYMENT_CALLBACK_TOKEN, $token)) {
    respond([
        'success' => false,
        'message' => 'Token callback tidak valid.',
    ], 403);
}

if ($bookingId <= 0 && $transactionId === '') {
    respond([
        'success' => false,
        'message' => 'booking_id atau transaction_id wajib diisi.',
    ], 400);
}

$lookupSql = 'SELECT b.id, b.status AS booking_status, p.id AS payment_id, p.status AS payment_status, p.transaction_id
    FROM booking b
    INNER JOIN pembayaran p ON p.booking_id = b.id
    WHERE 1=1';
$params = [];

if ($bookingId > 0) {
    $lookupSql .= ' AND b.id = :booking_id';
    $params['booking_id'] = $bookingId;
}

if ($transactionId !== '') {
    $lookupSql .= ' AND p.transaction_id = :transaction_id';
    $params['transaction_id'] = $transactionId;
}

$lookupSql .= ' LIMIT 1';
$stmt = $pdo->prepare($lookupSql);
$stmt->execute($params);
$booking = $stmt->fetch();

if (!$booking) {
    respond([
        'success' => false,
        'message' => 'Data booking tidak ditemukan.',
    ], 404);
}

$statusMap = [
    'success' => ['payment' => 'sukses', 'booking' => 'dibayar'],
    'sukses' => ['payment' => 'sukses', 'booking' => 'dibayar'],
    'paid' => ['payment' => 'sukses', 'booking' => 'dibayar'],
    'pending' => ['payment' => 'pending', 'booking' => 'pending'],
    'gagal' => ['payment' => 'gagal', 'booking' => 'batal'],
    'failed' => ['payment' => 'gagal', 'booking' => 'batal'],
    'cancel' => ['payment' => 'gagal', 'booking' => 'batal'],
    'batal' => ['payment' => 'gagal', 'booking' => 'batal'],
];

if ($status === '') {
    $status = 'success';
}

if (!isset($statusMap[$status])) {
    respond([
        'success' => false,
        'message' => 'Status tidak dikenali.',
    ], 400);
}

$targetPaymentStatus = $statusMap[$status]['payment'];
$targetBookingStatus = $statusMap[$status]['booking'];

try {
    $pdo->beginTransaction();

    $paymentUpdate = $pdo->prepare('UPDATE pembayaran SET status = :status, metode = :metode, transaction_id = COALESCE(NULLIF(:transaction_id, ""), transaction_id) WHERE booking_id = :booking_id');
    $paymentUpdate->execute([
        'status' => $targetPaymentStatus,
        'metode' => $metode,
        'transaction_id' => $transactionId,
        'booking_id' => (int) $booking['id'],
    ]);

    $bookingUpdate = $pdo->prepare('UPDATE booking SET status = :status WHERE id = :id');
    $bookingUpdate->execute([
        'status' => $targetBookingStatus,
        'id' => (int) $booking['id'],
    ]);

    $pdo->commit();

    respond([
        'success' => true,
        'message' => 'Status pembayaran berhasil diperbarui.',
        'data' => [
            'booking_id' => (int) $booking['id'],
            'payment_status' => $targetPaymentStatus,
            'booking_status' => $targetBookingStatus,
            'transaction_id' => $transactionId !== '' ? $transactionId : $booking['transaction_id'],
        ],
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    respond([
        'success' => false,
        'message' => 'Gagal memperbarui status pembayaran.',
    ], 500);
}
