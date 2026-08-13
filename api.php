<?php
// ===============================
// VINUTTO CRM - API ATUALIZADA
// Reescrita para o schema REAL de produção:
//   usuarios(id, nome, role, senha, criado_em, + colunas da migração v4)
//   leads(id, nome, tel, email, imovel, imovel_link, status, corretor_id,
//         data_cad, data_att, + colunas da migração v4)
//   historico(id, lead_id, data, status, nota, usuario_id)
// `id` em usuarios É o login (não existe coluna username separada).
// As consultas usam aliases SQL para manter o mesmo contrato JSON
// (name, corretor, imovelLink, dataCad, dataAtt, hist...) que o
// index.html já espera, para não precisar reescrever o front-end.
// ===============================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ===============================
// CONFIGURAÇÃO DO BANCO
// ===============================
$host = "localhost";
$db = "u744652542_crm";
$user = "u744652542_u744652542_crm";
$pass = "~*ykfUaZ2kC8";

// Config local de desenvolvimento (XAMPP etc.) - nunca subir este arquivo pro Hostinger.
$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    require $localConfig;
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão com o banco de dados']);
    exit;
}

// ===============================
// FUNÇÕES AUXILIARES
// ===============================

function audit(PDO $pdo, $userId, $action, $detail = '')
{
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, detail, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $action, $detail, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Exception $e) {
        // Log silencioso (tabela pode não existir ainda)
    }
}

function sendResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireAdmin($role)
{
    if ($role !== 'Admin' && $role !== 'Root') {
        sendResponse(['success' => false, 'error' => 'Acesso negado'], 403);
    }
}

// Verifica senha compatível com hash legado MD5 (formato original do
// sistema) e com bcrypt (password_hash), fazendo upgrade automático
// para bcrypt quando o login com MD5 for bem-sucedido.
function verifyAndMaybeUpgradePassword(PDO $pdo, $password, $storedHash, $userId)
{
    if (preg_match('/^[a-f0-9]{32}$/i', $storedHash)) {
        // Hash legado MD5
        $valid = (md5($password) === $storedHash);
        if ($valid) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmt->execute([$newHash, $userId]);
        }
        return $valid;
    }

    if (password_get_info($storedHash)['algo']) {
        return password_verify($password, $storedHash);
    }

    // Texto plano (fallback final)
    return $password === $storedHash;
}

// ===============================
// OBTER DADOS DA REQUISIÇÃO
// ===============================
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true) ?? [];

if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

// ===============================
// 1. LOGIN
// ===============================
if ($action === 'login') {
    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');

    if (empty($username) || empty($password)) {
        sendResponse(['success' => false, 'error' => 'Credenciais inválidas'], 400);
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT id, id AS username, nome AS name, role, senha, email, telefone, foto, coordenador_id
             FROM usuarios
             WHERE id = ? AND (ativo IS NULL OR ativo = 1)
             LIMIT 1"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            audit($pdo, null, 'login_failed', "Username: $username");
            sendResponse(['success' => false, 'error' => 'Usuário não encontrado'], 401);
        }

        $passwordValid = verifyAndMaybeUpgradePassword($pdo, $password, $user['senha'], $user['id']);

        if (!$passwordValid) {
            audit($pdo, $user['id'], 'login_failed', "Invalid password");
            sendResponse(['success' => false, 'error' => 'Senha incorreta'], 401);
        }

        $token = bin2hex(random_bytes(32));

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO sessions (user_id, token, created_at, expires_at, ip_address)
                 VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), ?)"
            );
            $stmt->execute([$user['id'], $token, $_SERVER['REMOTE_ADDR'] ?? null]);
        } catch (Exception $e) {
            // Se a tabela sessions não existir, continua sem token
        }

        try {
            $updateStmt = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
        } catch (Exception $e) {
            // Coluna pode não existir ainda antes da migração v4
        }

        audit($pdo, $user['id'], 'login_success');

        sendResponse([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'],
                'role' => $user['role'],
                'email' => $user['email'] ?? '',
                'telefone' => $user['telefone'] ?? '',
                'foto' => $user['foto'] ?? '',
                'coordenadorId' => $user['coordenador_id'] ?? null
            ]
        ]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao processar login: ' . $e->getMessage()], 500);
    }
}

// ===============================
// 2. LISTAR CORRETORES
// ===============================
if ($action === 'corretores' || $action === 'listCorretores') {
    try {
        $stmt = $pdo->query(
            "SELECT id, id AS username, nome AS name, email, telefone, role, coordenador_id
             FROM usuarios
             WHERE role IN ('Corretor', 'Corretora') AND (ativo IS NULL OR ativo = 1)
             ORDER BY nome"
        );
        $corretores = $stmt->fetchAll();

        sendResponse(['success' => true, 'corretores' => $corretores]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao listar corretores: ' . $e->getMessage()], 500);
    }
}

// ===============================
// 3. LISTAR LEADS
// ===============================
if ($action === 'list' || $action === 'listLeads') {
    $corretor = $_GET['corretor'] ?? $data['corretor'] ?? '';
    $role = $_GET['role'] ?? $data['role'] ?? '';
    $status = $_GET['status'] ?? $data['status'] ?? '';
    $search = $_GET['search'] ?? $data['search'] ?? '';
    $origemId = $_GET['origemId'] ?? $data['origemId'] ?? '';
    $empreendimentoId = $_GET['empreendimentoId'] ?? $data['empreendimentoId'] ?? '';
    $cidade = $_GET['cidade'] ?? $data['cidade'] ?? '';
    $dataInicio = $_GET['dataInicio'] ?? $data['dataInicio'] ?? '';
    $dataFim = $_GET['dataFim'] ?? $data['dataFim'] ?? '';

    try {
        $sql = "SELECT l.id, l.nome, l.tel, l.email, l.imovel,
                       l.imovel_link AS imovelLink,
                       l.corretor_id AS corretor,
                       l.status, l.feedback,
                       l.data_cad AS dataCad, l.data_att AS dataAtt,
                       l.cpf, l.whatsapp, l.cidade, l.estado,
                       l.origem_id, l.empreendimento_id, l.construtora_id,
                       l.tipo_imovel, l.quartos, l.valor_min, l.valor_max,
                       l.valor_fechado, l.comissao_percentual, l.comissao_valor, l.comissao_recebida,
                       u.nome as corretor_nome,
                       e.nome as empreendimento_nome,
                       c.nome as construtora_nome,
                       o.nome as origem_nome
                FROM leads l
                LEFT JOIN usuarios u ON l.corretor_id = u.id
                LEFT JOIN empreendimentos e ON l.empreendimento_id = e.id
                LEFT JOIN construtoras c ON l.construtora_id = c.id
                LEFT JOIN origens o ON l.origem_id = o.id
                WHERE 1=1";
        $params = [];

        if ($role === 'Coordenador' && $corretor !== '') {
            $sql .= " AND (l.corretor_id = ? OR u.coordenador_id = ?)";
            $params[] = $corretor;
            $params[] = $corretor;
        } elseif ($role !== 'Admin' && $role !== 'Root' && $corretor !== '') {
            $sql .= " AND l.corretor_id = ?";
            $params[] = $corretor;
        }

        if (!empty($status)) {
            $sql .= " AND l.status = ?";
            $params[] = $status;
        }

        if (!empty($origemId)) {
            $sql .= " AND l.origem_id = ?";
            $params[] = $origemId;
        }

        if (!empty($empreendimentoId)) {
            $sql .= " AND l.empreendimento_id = ?";
            $params[] = $empreendimentoId;
        }

        if (!empty($cidade)) {
            $sql .= " AND l.cidade = ?";
            $params[] = $cidade;
        }

        if (!empty($dataInicio)) {
            $sql .= " AND l.data_cad >= ?";
            $params[] = $dataInicio . ' 00:00:00';
        }

        if (!empty($dataFim)) {
            $sql .= " AND l.data_cad <= ?";
            $params[] = $dataFim . ' 23:59:59';
        }

        if (!empty($search)) {
            $sql .= " AND (l.nome LIKE ? OR l.email LIKE ? OR l.tel LIKE ? OR l.whatsapp LIKE ? OR l.cpf LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        $sql .= " ORDER BY l.data_att DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $leads = $stmt->fetchAll();

        if (!empty($leads)) {
            $leadIds = array_column($leads, 'id');
            $placeholders = implode(',', array_fill(0, count($leadIds), '?'));

            $histStmt = $pdo->prepare(
                "SELECT lead_id, status, nota, usuario_id, data
                 FROM historico
                 WHERE lead_id IN ($placeholders)
                 ORDER BY data ASC"
            );
            $histStmt->execute($leadIds);
            $historicoPorLead = [];
            foreach ($histStmt->fetchAll() as $h) {
                $historicoPorLead[$h['lead_id']][] = [
                    'data' => date('d/m/Y H:i', strtotime($h['data'])),
                    'note' => $h['nota'],
                    'status' => $h['status'],
                    'usuario' => $h['usuario_id']
                ];
            }
        }

        foreach ($leads as &$lead) {
            $lead['hist'] = $historicoPorLead[$lead['id']] ?? [];
        }

        sendResponse(['success' => true, 'leads' => $leads]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao listar leads: ' . $e->getMessage()], 500);
    }
}

// ===============================
// 4. SALVAR/ATUALIZAR LEAD
// ===============================
if ($action === 'save' || $action === 'saveLead') {
    $leadId = $data['id'] ?? $data['id_update'] ?? null;
    $userId = $data['userId'] ?? null;
    $status = $data['status'] ?? 'atendimento';

    $extraFields = [
        'cpf' => $data['cpf'] ?? null,
        'whatsapp' => $data['whatsapp'] ?? null,
        'cidade' => $data['cidade'] ?? null,
        'estado' => $data['estado'] ?? null,
        'origem_id' => $data['origemId'] ?? null,
        'empreendimento_id' => $data['empreendimentoId'] ?? null,
        'construtora_id' => $data['construtoraId'] ?? null,
        'tipo_imovel' => $data['tipoImovel'] ?? null,
        'quartos' => $data['quartos'] ?? null,
        'valor_min' => $data['valorMin'] ?? null,
        'valor_max' => $data['valorMax'] ?? null,
        'valor_fechado' => $data['valorFechado'] ?? null,
        'comissao_percentual' => $data['comissaoPercentual'] ?? null,
        'comissao_recebida' => isset($data['comissaoRecebida']) ? (int)!!$data['comissaoRecebida'] : 0,
    ];

    try {
        if (!empty($leadId)) {
            // ATUALIZAR LEAD EXISTENTE
            $stmt = $pdo->prepare(
                "UPDATE leads
                 SET tel = ?, email = ?, imovel = ?, imovel_link = ?,
                     status = ?, feedback = ?,
                     cpf = ?, whatsapp = ?, cidade = ?, estado = ?,
                     origem_id = ?, empreendimento_id = ?, construtora_id = ?,
                     tipo_imovel = ?, quartos = ?, valor_min = ?, valor_max = ?,
                     valor_fechado = ?, comissao_percentual = ?, comissao_recebida = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $data['tel'] ?? '',
                $data['email'] ?? '',
                $data['imovel'] ?? '',
                $data['imovelLink'] ?? '',
                $status,
                $data['feedback'] ?? '',
                $extraFields['cpf'], $extraFields['whatsapp'], $extraFields['cidade'], $extraFields['estado'],
                $extraFields['origem_id'], $extraFields['empreendimento_id'], $extraFields['construtora_id'],
                $extraFields['tipo_imovel'], $extraFields['quartos'], $extraFields['valor_min'], $extraFields['valor_max'],
                $extraFields['valor_fechado'], $extraFields['comissao_percentual'], $extraFields['comissao_recebida'],
                $leadId
            ]);

            $histStmt = $pdo->prepare(
                "INSERT INTO historico (lead_id, data, status, nota, usuario_id) VALUES (?, NOW(), ?, ?, ?)"
            );
            $histStmt->execute([$leadId, $status, $data['note'] ?? $data['feedback'] ?? '', $userId]);

            audit($pdo, $userId, 'update_lead', "Lead ID: $leadId");
            sendResponse(['success' => true, 'message' => 'Lead atualizado com sucesso', 'leadId' => $leadId]);
        } else {
            // CRIAR NOVO LEAD
            $nome = trim($data['nome'] ?? '');
            if (empty($nome)) {
                sendResponse(['success' => false, 'error' => 'Nome é obrigatório'], 400);
            }

            $stmt = $pdo->prepare(
                "INSERT INTO leads
                 (nome, tel, email, imovel, imovel_link, corretor_id, status, feedback, data_cad, data_att,
                  cpf, whatsapp, cidade, estado, origem_id, empreendimento_id, construtora_id,
                  tipo_imovel, quartos, valor_min, valor_max)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $nome,
                $data['tel'] ?? '',
                $data['email'] ?? '',
                $data['imovel'] ?? 'N/A',
                $data['imovelLink'] ?? '',
                $data['corretor'] ?? $userId,
                $status,
                $data['feedback'] ?? 'Lead cadastrado no sistema',
                $extraFields['cpf'], $extraFields['whatsapp'], $extraFields['cidade'], $extraFields['estado'],
                $extraFields['origem_id'], $extraFields['empreendimento_id'], $extraFields['construtora_id'],
                $extraFields['tipo_imovel'], $extraFields['quartos'], $extraFields['valor_min'], $extraFields['valor_max']
            ]);

            $newLeadId = $pdo->lastInsertId();

            $histStmt = $pdo->prepare(
                "INSERT INTO historico (lead_id, data, status, nota, usuario_id) VALUES (?, NOW(), ?, ?, ?)"
            );
            $histStmt->execute([$newLeadId, $status, 'Lead cadastrado no sistema', $userId]);

            audit($pdo, $userId, 'create_lead', "Lead: $nome (ID: $newLeadId)");
            sendResponse(['success' => true, 'message' => 'Lead cadastrado com sucesso', 'leadId' => $newLeadId]);
        }
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao salvar lead: ' . $e->getMessage()], 500);
    }
}

// ===============================
// 4.1 DELETAR LEAD (ADMIN)
// ===============================
if ($action === 'deleteLead') {
    $role = $_GET['role'] ?? $data['role'] ?? '';
    requireAdmin($role);

    $leadId = $data['leadId'] ?? null;
    if (!$leadId) {
        sendResponse(['success' => false, 'error' => 'ID do lead é obrigatório'], 400);
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ?");
        $stmt->execute([$leadId]);

        audit($pdo, $data['userId'] ?? null, 'delete_lead', "Lead ID: $leadId");
        sendResponse(['success' => true, 'message' => 'Lead removido com sucesso']);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao remover lead'], 500);
    }
}

// ===============================
// 5. EXPORTAR LEADS (ADMIN)
// ===============================
if ($action === 'export' || $action === 'exportLeads') {
    $role = $_GET['role'] ?? $data['role'] ?? '';
    requireAdmin($role);

    try {
        $stmt = $pdo->query(
            "SELECT l.id, l.nome, l.tel, l.email, l.imovel,
                    l.imovel_link AS imovelLink, l.corretor_id AS corretor,
                    l.status, l.feedback, l.data_cad AS dataCad, l.data_att AS dataAtt,
                    l.cpf, l.cidade, l.valor_fechado, l.comissao_valor,
                    u.nome as corretor_nome,
                    e.nome as empreendimento_nome, c.nome as construtora_nome, o.nome as origem_nome
             FROM leads l
             LEFT JOIN usuarios u ON l.corretor_id = u.id
             LEFT JOIN empreendimentos e ON l.empreendimento_id = e.id
             LEFT JOIN construtoras c ON l.construtora_id = c.id
             LEFT JOIN origens o ON l.origem_id = o.id
             ORDER BY l.data_cad DESC"
        );
        $leads = $stmt->fetchAll();

        audit($pdo, 'admin', 'export_leads', count($leads) . ' leads exportados');

        sendResponse(['success' => true, 'leads' => $leads]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao exportar leads'], 500);
    }
}

// ===============================
// 6. CADASTRAR USUÁRIO (ADMIN)
// ===============================
if ($action === 'createUser') {
    $adminRole = $data['adminRole'] ?? '';
    requireAdmin($adminRole);

    // Não há coluna username separada: o "username" digitado pelo admin
    // vira o próprio id (chave primária) do usuário, ex: 'novo.corretor'.
    $id = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '123');
    $name = trim($data['name'] ?? '');
    $role = trim($data['role'] ?? 'Corretor');
    $email = trim($data['email'] ?? '');
    $telefone = trim($data['telefone'] ?? '');
    $creci = trim($data['creci'] ?? '');
    $whatsapp = trim($data['whatsapp'] ?? '');
    $cpf = trim($data['cpf'] ?? '');
    $coordenadorId = !empty($data['coordenadorId']) ? $data['coordenadorId'] : null;

    if (empty($id) || empty($name)) {
        sendResponse(['success' => false, 'error' => 'Campos obrigatórios: username, name'], 400);
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            sendResponse(['success' => false, 'error' => 'Username já existe'], 409);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (id, nome, role, senha, email, telefone, creci, whatsapp, cpf, coordenador_id, ativo, criado_em)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())"
        );
        $stmt->execute([$id, $name, $role, $passwordHash, $email, $telefone, $creci, $whatsapp, $cpf, $coordenadorId]);

        audit($pdo, $data['adminId'] ?? null, 'create_user', "New user: $id ($name)");

        sendResponse([
            'success' => true,
            'userId' => $id,
            'message' => 'Usuário cadastrado com sucesso'
        ]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao cadastrar usuário: ' . $e->getMessage()], 500);
    }
}

// ===============================
// 6.1 ATUALIZAR USUÁRIO (ADMIN)
// ===============================
if ($action === 'updateUser') {
    $adminRole = $data['adminRole'] ?? '';
    requireAdmin($adminRole);

    $userId = $data['userId'] ?? null;
    if (!$userId) {
        sendResponse(['success' => false, 'error' => 'ID do usuário é obrigatório'], 400);
    }

    try {
        $stmt = $pdo->prepare(
            "UPDATE usuarios
             SET nome = ?, email = ?, telefone = ?, role = ?, ativo = ?,
                 creci = ?, whatsapp = ?, cpf = ?, coordenador_id = ?, meta_mensal = ?, comissao_percentual = ?
             WHERE id = ?"
        );
        $stmt->execute([
            $data['name'] ?? '',
            $data['email'] ?? '',
            $data['telefone'] ?? '',
            $data['role'] ?? 'Corretor',
            isset($data['ativo']) ? (int)!!$data['ativo'] : 1,
            $data['creci'] ?? null,
            $data['whatsapp'] ?? null,
            $data['cpf'] ?? null,
            !empty($data['coordenadorId']) ? $data['coordenadorId'] : null,
            $data['metaMensal'] ?? null,
            $data['comissaoPercentual'] ?? null,
            $userId
        ]);

        audit($pdo, $data['adminId'] ?? null, 'update_user', "User ID: $userId");
        sendResponse(['success' => true, 'message' => 'Usuário atualizado com sucesso']);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao atualizar usuário: ' . $e->getMessage()], 500);
    }
}

// ===============================
// 6.2 DELETAR USUÁRIO (ADMIN) - SOFT DELETE
// ===============================
if ($action === 'deleteUser') {
    $adminRole = $data['adminRole'] ?? $data['role'] ?? '';
    requireAdmin($adminRole);

    $userId = $data['userId'] ?? null;
    $adminId = $data['adminId'] ?? $data['userId'] ?? null;

    // Preferir identidade do token se disponível
    $token = $data['token'] ?? null;
    if (!empty($token)) {
        try {
            $st = $pdo->prepare("SELECT u.id, u.role FROM sessions s JOIN usuarios u ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW() LIMIT 1");
            $st->execute([$token]);
            $au = $st->fetch();
            if ($au) {
                $adminId = $au['id'];
                $adminRole = $au['role'];
            }
        } catch (Exception $e) {}
    }

    if (!$userId) {
        sendResponse(['success' => false, 'error' => 'ID do usuário é obrigatório'], 400);
    }

    if (!$adminId) {
        sendResponse(['success' => false, 'error' => 'Admin não identificado'], 400);
    }

    if ((string)$userId === (string)$adminId) {
        sendResponse(['success' => false, 'error' => 'Você não pode excluir seu próprio usuário'], 400);
    }

    $chk = $pdo->prepare("SELECT id, role FROM usuarios WHERE id = ?");
    $chk->execute([$userId]);
    $target = $chk->fetch();
    if (!$target) {
        sendResponse(['success' => false, 'error' => 'Usuário não encontrado'], 404);
    }

    if (($target['role'] ?? '') === 'Root' || (string)$userId === 'Root') {
        sendResponse(['success' => false, 'error' => 'O usuário Root não pode ser excluído'], 403);
    }

    // Não excluir o único Admin (Root pode excluir Admin)
    if (($target['role'] ?? '') === 'Admin') {
        if ($adminRole !== 'Root') {
            $cnt = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE role = 'Admin' AND (ativo IS NULL OR ativo = 1)")->fetchColumn();
            if ($cnt <= 1) {
                sendResponse(['success' => false, 'error' => 'Não é possível excluir o único administrador'], 400);
            }
            // Admin não exclui outro Admin — só Root
            sendResponse(['success' => false, 'error' => 'Somente o Root pode excluir um administrador'], 403);
        }
    }

    try {
        $pdo->beginTransaction();

        // Transfere leads do usuário excluído para o admin logado
        $transfer = $pdo->prepare("UPDATE leads SET corretor_id = ?, data_att = NOW() WHERE corretor_id = ?");
        $transfer->execute([$adminId, $userId]);
        $leadsMoved = $transfer->rowCount();

        try {
            $pdo->prepare("UPDATE usuarios SET coordenador_id = NULL WHERE coordenador_id = ?")->execute([$userId]);
        } catch (Exception $e) {}

        try {
            $pdo->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$userId]);
        } catch (Exception $e) {}

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            sendResponse(['success' => false, 'error' => 'Usuário não encontrado'], 404);
        }

        $pdo->commit();

        audit($pdo, $adminId, 'delete_user', "User ID: $userId excluído. $leadsMoved lead(s) → $adminId");
        sendResponse([
            'success' => true,
            'message' => "Usuário excluído. $leadsMoved lead(s) transferidos para você.",
            'leadsTransferred' => $leadsMoved
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        sendResponse(['success' => false, 'error' => 'Erro ao excluir usuário: ' . $e->getMessage()], 500);
    }
}

// ===============================
// 7. LISTAR USUÁRIOS (ADMIN)
// ===============================
if ($action === 'users' || $action === 'listUsers') {
    $role = $_GET['role'] ?? $data['role'] ?? '';
    requireAdmin($role);

    try {
        $stmt = $pdo->query(
            "SELECT id, id AS username, nome AS name, role, email, telefone, creci, whatsapp, cpf,
                    coordenador_id, meta_mensal, comissao_percentual, ativo, ultimo_login, criado_em AS created_at
             FROM usuarios
             ORDER BY nome"
        );
        $users = $stmt->fetchAll();

        sendResponse(['success' => true, 'users' => $users]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao listar usuários: ' . $e->getMessage()], 500);
    }
}

// ===============================
// 8. RESET DE SENHA (ADMIN)
// ===============================
if ($action === 'resetPassword') {
    $adminRole = $data['adminRole'] ?? '';
    $userId = $data['userId'] ?? null;
    $newPassword = trim($data['newPassword'] ?? '123');

    requireAdmin($adminRole);

    if (!$userId) {
        sendResponse(['success' => false, 'error' => 'ID do usuário é obrigatório'], 400);
    }

    try {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $userId]);

        audit($pdo, $data['adminId'] ?? null, 'reset_password', "User ID: $userId");

        sendResponse(['success' => true, 'message' => 'Senha resetada com sucesso']);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao resetar senha'], 500);
    }
}

// ===============================
// 9. ALTERAR PRÓPRIA SENHA
// ===============================
if ($action === 'changePassword') {
    $userId = $data['userId'] ?? null;
    $currentPassword = trim($data['currentPassword'] ?? '');
    $newPassword = trim($data['newPassword'] ?? '');

    if (!$userId || empty($currentPassword) || empty($newPassword)) {
        sendResponse(['success' => false, 'error' => 'Dados incompletos'], 400);
    }

    if (strlen($newPassword) < 3) {
        sendResponse(['success' => false, 'error' => 'A senha deve ter no mínimo 3 caracteres'], 400);
    }

    try {
        $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            sendResponse(['success' => false, 'error' => 'Usuário não encontrado'], 404);
        }

        $passwordValid = verifyAndMaybeUpgradePassword($pdo, $currentPassword, $user['senha'], $userId);

        if (!$passwordValid) {
            sendResponse(['success' => false, 'error' => 'Senha atual incorreta'], 401);
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $userId]);

        audit($pdo, $userId, 'change_password');

        sendResponse(['success' => true, 'message' => 'Senha alterada com sucesso']);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao alterar senha'], 500);
    }
}

// ===============================
// 10. ESTATÍSTICAS
// ===============================
if ($action === 'stats' || $action === 'dashboard') {
    $corretor = $_GET['corretor'] ?? $data['corretor'] ?? '';
    $role = $_GET['role'] ?? $data['role'] ?? '';

    try {
        $whereClause = '';
        $params = [];

        if ($role === 'Coordenador' && $corretor !== '') {
            $whereClause = "WHERE (l.corretor_id = ? OR u.coordenador_id = ?)";
            $params = [$corretor, $corretor];
        } elseif ($role !== 'Admin' && $role !== 'Root' && $corretor !== '') {
            $whereClause = "WHERE l.corretor_id = ?";
            $params = [$corretor];
        }

        $base = "FROM leads l LEFT JOIN usuarios u ON l.corretor_id = u.id $whereClause";

        $stmt = $pdo->prepare("SELECT COUNT(*) as total $base");
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $stmt = $pdo->prepare("SELECT l.status, COUNT(*) as count $base GROUP BY l.status");
        $stmt->execute($params);
        $byStatus = $stmt->fetchAll();

        $whereToday = $whereClause ? "$whereClause AND DATE(l.data_cad) = CURDATE()" : "WHERE DATE(l.data_cad) = CURDATE()";
        $stmt = $pdo->prepare("SELECT COUNT(*) as today FROM leads l LEFT JOIN usuarios u ON l.corretor_id = u.id $whereToday");
        $stmt->execute($params);
        $today = $stmt->fetch()['today'];

        $whereMonth = $whereClause
            ? "$whereClause AND MONTH(l.data_cad) = MONTH(CURDATE()) AND YEAR(l.data_cad) = YEAR(CURDATE())"
            : "WHERE MONTH(l.data_cad) = MONTH(CURDATE()) AND YEAR(l.data_cad) = YEAR(CURDATE())";
        $stmt = $pdo->prepare("SELECT COUNT(*) as month FROM leads l LEFT JOIN usuarios u ON l.corretor_id = u.id $whereMonth");
        $stmt->execute($params);
        $month = $stmt->fetch()['month'];

        $statusGroups = [
            'visitas' => ['visita_agendada', 'visita'],
            'negociacao' => ['em_negociacao', 'negociacao', 'aguardando_aprovacao', 'proposta_enviada', 'contrato_enviado'],
            'fechados' => ['negocio_fechado', 'fechado', 'venda'],
            'perdidos' => ['negocio_perdido', 'perdido'],
        ];
        $statusCounts = [];
        foreach ($statusGroups as $key => $statuses) {
            $ph = implode(',', array_fill(0, count($statuses), '?'));
            $extraWhere = $whereClause ? "$whereClause AND l.status IN ($ph)" : "WHERE l.status IN ($ph)";
            $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM leads l LEFT JOIN usuarios u ON l.corretor_id = u.id $extraWhere");
            $stmt->execute(array_merge($params, $statuses));
            $statusCounts[$key] = (int)$stmt->fetch()['c'];
        }

        $negociacaoStatuses = $statusGroups['negociacao'];
        $ph = implode(',', array_fill(0, count($negociacaoStatuses), '?'));
        $extraWhere = $whereClause ? "$whereClause AND l.status IN ($ph)" : "WHERE l.status IN ($ph)";
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(l.valor_max),0) as v FROM leads l LEFT JOIN usuarios u ON l.corretor_id = u.id $extraWhere");
        $stmt->execute(array_merge($params, $negociacaoStatuses));
        $valorEmNegociacao = (float)$stmt->fetch()['v'];

        $fechadoStatuses = $statusGroups['fechados'];
        $ph2 = implode(',', array_fill(0, count($fechadoStatuses), '?'));
        $extraWhere2 = $whereClause ? "$whereClause AND l.status IN ($ph2)" : "WHERE l.status IN ($ph2)";
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(l.valor_fechado),0) as v, COALESCE(SUM(l.comissao_valor),0) as com,
                                      COALESCE(SUM(CASE WHEN l.comissao_recebida = 1 THEN l.comissao_valor ELSE 0 END),0) as comRecebida
                               FROM leads l LEFT JOIN usuarios u ON l.corretor_id = u.id $extraWhere2");
        $stmt->execute(array_merge($params, $fechadoStatuses));
        $fechadosRow = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT o.nome as origem, COUNT(*) as count
                                FROM leads l
                                LEFT JOIN usuarios u ON l.corretor_id = u.id
                                LEFT JOIN origens o ON l.origem_id = o.id
                                $whereClause
                                GROUP BY o.nome");
        $stmt->execute($params);
        $byOrigem = $stmt->fetchAll();

        $byCorretor = [];
        if ($role === 'Admin' || $role === 'Root' || $role === 'Coordenador') {
            $stmt = $pdo->prepare("SELECT u.nome as corretor, COUNT(*) as count
                                    FROM leads l
                                    LEFT JOIN usuarios u ON l.corretor_id = u.id
                                    $whereClause
                                    GROUP BY u.nome");
            $stmt->execute($params);
            $byCorretor = $stmt->fetchAll();
        }

        sendResponse([
            'success' => true,
            'stats' => [
                'total' => (int)$total,
                'today' => (int)$today,
                'month' => (int)$month,
                'visitasMarcadas' => $statusCounts['visitas'],
                'emNegociacao' => $statusCounts['negociacao'],
                'negociosFechados' => $statusCounts['fechados'],
                'negociosPerdidos' => $statusCounts['perdidos'],
                'valorEmNegociacao' => $valorEmNegociacao,
                'valorFechado' => (float)$fechadosRow['v'],
                'comissaoPrevista' => (float)$fechadosRow['com'],
                'comissaoRecebida' => (float)$fechadosRow['comRecebida'],
                'byStatus' => $byStatus,
                'byOrigem' => $byOrigem,
                'byCorretor' => $byCorretor
            ]
        ]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao obter estatísticas: ' . $e->getMessage()], 500);
    }
}

// ===============================
// 11. CONSTRUTORAS
// ===============================
if ($action === 'listConstrutoras') {
    try {
        $stmt = $pdo->query("SELECT * FROM construtoras WHERE ativo = 1 ORDER BY nome");
        sendResponse(['success' => true, 'construtoras' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao listar construtoras'], 500);
    }
}

if ($action === 'saveConstrutora') {
    $role = $data['role'] ?? '';
    requireAdmin($role);

    $id = $data['id'] ?? null;
    $nome = trim($data['nome'] ?? '');
    if (empty($nome)) {
        sendResponse(['success' => false, 'error' => 'Nome é obrigatório'], 400);
    }

    try {
        if (!empty($id)) {
            $stmt = $pdo->prepare("UPDATE construtoras SET nome=?, cnpj=?, telefone=?, email=?, site=?, observacoes=? WHERE id=?");
            $stmt->execute([$nome, $data['cnpj'] ?? null, $data['telefone'] ?? null, $data['email'] ?? null, $data['site'] ?? null, $data['observacoes'] ?? null, $id]);
            sendResponse(['success' => true, 'message' => 'Construtora atualizada', 'id' => $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO construtoras (nome, cnpj, telefone, email, site, observacoes) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$nome, $data['cnpj'] ?? null, $data['telefone'] ?? null, $data['email'] ?? null, $data['site'] ?? null, $data['observacoes'] ?? null]);
            sendResponse(['success' => true, 'message' => 'Construtora cadastrada', 'id' => $pdo->lastInsertId()]);
        }
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao salvar construtora: ' . $e->getMessage()], 500);
    }
}

if ($action === 'deleteConstrutora') {
    $role = $data['role'] ?? '';
    requireAdmin($role);

    $id = $data['id'] ?? null;
    if (!$id) {
        sendResponse(['success' => false, 'error' => 'ID é obrigatório'], 400);
    }

    try {
        $stmt = $pdo->prepare("UPDATE construtoras SET ativo = 0 WHERE id = ?");
        $stmt->execute([$id]);
        sendResponse(['success' => true, 'message' => 'Construtora removida']);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao remover construtora'], 500);
    }
}

// ===============================
// 12. EMPREENDIMENTOS
// ===============================
if ($action === 'listEmpreendimentos') {
    try {
        $stmt = $pdo->query(
            "SELECT e.*, c.nome as construtora_nome
             FROM empreendimentos e
             LEFT JOIN construtoras c ON e.construtora_id = c.id
             WHERE e.status = 'ativo'
             ORDER BY e.nome"
        );
        sendResponse(['success' => true, 'empreendimentos' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao listar empreendimentos'], 500);
    }
}

if ($action === 'saveEmpreendimento') {
    $role = $data['role'] ?? '';
    requireAdmin($role);

    $id = $data['id'] ?? null;
    $nome = trim($data['nome'] ?? '');
    if (empty($nome)) {
        sendResponse(['success' => false, 'error' => 'Nome é obrigatório'], 400);
    }

    try {
        if (!empty($id)) {
            $stmt = $pdo->prepare(
                "UPDATE empreendimentos
                 SET nome=?, construtora_id=?, endereco=?, cidade=?, estado=?, tipo=?, valor_min=?, valor_max=?, tabela_precos_url=?
                 WHERE id=?"
            );
            $stmt->execute([
                $nome, $data['construtoraId'] ?? null, $data['endereco'] ?? null, $data['cidade'] ?? null,
                $data['estado'] ?? null, $data['tipo'] ?? null, $data['valorMin'] ?? null, $data['valorMax'] ?? null,
                $data['tabelaPrecosUrl'] ?? null, $id
            ]);
            sendResponse(['success' => true, 'message' => 'Empreendimento atualizado', 'id' => $id]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO empreendimentos (nome, construtora_id, endereco, cidade, estado, tipo, valor_min, valor_max, tabela_precos_url)
                 VALUES (?,?,?,?,?,?,?,?,?)"
            );
            $stmt->execute([
                $nome, $data['construtoraId'] ?? null, $data['endereco'] ?? null, $data['cidade'] ?? null,
                $data['estado'] ?? null, $data['tipo'] ?? null, $data['valorMin'] ?? null, $data['valorMax'] ?? null,
                $data['tabelaPrecosUrl'] ?? null
            ]);
            sendResponse(['success' => true, 'message' => 'Empreendimento cadastrado', 'id' => $pdo->lastInsertId()]);
        }
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao salvar empreendimento: ' . $e->getMessage()], 500);
    }
}

if ($action === 'deleteEmpreendimento') {
    $role = $data['role'] ?? '';
    requireAdmin($role);

    $id = $data['id'] ?? null;
    if (!$id) {
        sendResponse(['success' => false, 'error' => 'ID é obrigatório'], 400);
    }

    try {
        $stmt = $pdo->prepare("UPDATE empreendimentos SET status = 'inativo' WHERE id = ?");
        $stmt->execute([$id]);
        sendResponse(['success' => true, 'message' => 'Empreendimento removido']);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao remover empreendimento'], 500);
    }
}

// ===============================
// 13. ORIGENS
// ===============================
if ($action === 'listOrigens') {
    try {
        $stmt = $pdo->query("SELECT * FROM origens WHERE ativo = 1 ORDER BY nome");
        sendResponse(['success' => true, 'origens' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao listar origens'], 500);
    }
}

if ($action === 'saveOrigem') {
    $role = $data['role'] ?? '';
    requireAdmin($role);

    $nome = trim($data['nome'] ?? '');
    if (empty($nome)) {
        sendResponse(['success' => false, 'error' => 'Nome é obrigatório'], 400);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO origens (nome) VALUES (?) ON DUPLICATE KEY UPDATE ativo = 1");
        $stmt->execute([$nome]);
        sendResponse(['success' => true, 'message' => 'Origem cadastrada']);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao salvar origem'], 500);
    }
}

if ($action === 'deleteOrigem') {
    $role = $data['role'] ?? '';
    requireAdmin($role);

    $id = $data['id'] ?? null;
    if (!$id) {
        sendResponse(['success' => false, 'error' => 'ID é obrigatório'], 400);
    }

    try {
        $stmt = $pdo->prepare("UPDATE origens SET ativo = 0 WHERE id = ?");
        $stmt->execute([$id]);
        sendResponse(['success' => true, 'message' => 'Origem removida']);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao remover origem'], 500);
    }
}

// ===============================
// 14. OBSERVAÇÕES
// ===============================
if ($action === 'listObservacoes') {
    $leadId = $_GET['leadId'] ?? $data['leadId'] ?? null;
    if (!$leadId) {
        sendResponse(['success' => false, 'error' => 'ID do lead é obrigatório'], 400);
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT ob.*, u.nome as usuario_nome
             FROM observacoes ob
             LEFT JOIN usuarios u ON ob.usuario_id = u.id
             WHERE ob.lead_id = ?
             ORDER BY ob.created_at DESC"
        );
        $stmt->execute([$leadId]);
        sendResponse(['success' => true, 'observacoes' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao listar observações: ' . $e->getMessage()], 500);
    }
}

if ($action === 'addObservacao') {
    $leadId = $data['leadId'] ?? null;
    $texto = trim($data['texto'] ?? '');
    $userId = $data['userId'] ?? null;

    if (!$leadId || empty($texto)) {
        sendResponse(['success' => false, 'error' => 'Lead e texto são obrigatórios'], 400);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO observacoes (lead_id, usuario_id, texto, created_at) VALUES (?,?,?,NOW())");
        $stmt->execute([$leadId, $userId, $texto]);
        $newObservacaoId = $pdo->lastInsertId();

        audit($pdo, $userId, 'add_observacao', "Lead ID: $leadId");
        sendResponse(['success' => true, 'message' => 'Observação adicionada', 'id' => $newObservacaoId]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao adicionar observação: ' . $e->getMessage()], 500);
    }
}

// ===============================
// 15. TAREFAS
// ===============================
if ($action === 'listTarefas') {
    $leadId = $_GET['leadId'] ?? $data['leadId'] ?? '';
    $responsavelId = $_GET['responsavelId'] ?? $data['responsavelId'] ?? '';
    $status = $_GET['status'] ?? $data['status'] ?? '';

    try {
        $sql = "SELECT t.*, u.nome as responsavel_nome, l.nome as lead_nome
                FROM tarefas t
                LEFT JOIN usuarios u ON t.responsavel_id = u.id
                LEFT JOIN leads l ON t.lead_id = l.id
                WHERE 1=1";
        $params = [];

        if (!empty($leadId)) {
            $sql .= " AND t.lead_id = ?";
            $params[] = $leadId;
        }
        if (!empty($responsavelId)) {
            $sql .= " AND t.responsavel_id = ?";
            $params[] = $responsavelId;
        }
        if (!empty($status)) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY t.prazo ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        sendResponse(['success' => true, 'tarefas' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao listar tarefas: ' . $e->getMessage()], 500);
    }
}

if ($action === 'addTarefa') {
    $titulo = trim($data['titulo'] ?? '');
    if (empty($titulo)) {
        sendResponse(['success' => false, 'error' => 'Título é obrigatório'], 400);
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO tarefas (lead_id, titulo, descricao, responsavel_id, prazo, prioridade, status)
             VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $data['leadId'] ?? null,
            $titulo,
            $data['descricao'] ?? null,
            $data['responsavelId'] ?? null,
            $data['prazo'] ?? null,
            $data['prioridade'] ?? 'media',
            $data['status'] ?? 'pendente'
        ]);

        sendResponse(['success' => true, 'message' => 'Tarefa criada', 'id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao criar tarefa: ' . $e->getMessage()], 500);
    }
}

if ($action === 'updateTarefa') {
    $id = $data['id'] ?? null;
    if (!$id) {
        sendResponse(['success' => false, 'error' => 'ID é obrigatório'], 400);
    }

    try {
        $stmt = $pdo->prepare(
            "UPDATE tarefas SET titulo=?, descricao=?, responsavel_id=?, prazo=?, prioridade=?, status=? WHERE id=?"
        );
        $stmt->execute([
            $data['titulo'] ?? '',
            $data['descricao'] ?? null,
            $data['responsavelId'] ?? null,
            $data['prazo'] ?? null,
            $data['prioridade'] ?? 'media',
            $data['status'] ?? 'pendente',
            $id
        ]);

        sendResponse(['success' => true, 'message' => 'Tarefa atualizada']);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao atualizar tarefa'], 500);
    }
}

// ===============================
// REASSIGN LEADS (Admin lote / Corretor unitário)
// ===============================
if ($action === 'reassignLeads') {
    $role = $data['role'] ?? $_GET['role'] ?? '';
    $userId = $data['userId'] ?? $data['adminId'] ?? null;
    $toUserId = $data['toUserId'] ?? null;
    $fromUserId = $data['fromUserId'] ?? $userId;
    $leadIds = $data['leadIds'] ?? null;
    $token = $data['token'] ?? null;

    // Prefer token auth if sessions available
    $authId = $userId;
    $authRole = $role;
    try {
        if (!empty($token)) {
            $st = $pdo->prepare("SELECT u.id, u.role FROM sessions s JOIN usuarios u ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW() LIMIT 1");
            $st->execute([$token]);
            $au = $st->fetch();
            if ($au) { $authId = $au['id']; $authRole = $au['role']; }
        }
    } catch (Exception $e) {}

    if (!$toUserId) {
        sendResponse(['success' => false, 'error' => 'Informe o corretor de destino'], 400);
    }

    $isAdmin = in_array($authRole, ['Admin', 'Root'], true);
    $isCoord = ($authRole === 'Coordenador');
    $isCorretor = in_array($authRole, ['Corretor', 'Corretora'], true);

    if (!$isAdmin && !$isCoord && !$isCorretor) {
        sendResponse(['success' => false, 'error' => 'Sem permissão'], 403);
    }

    try {
        $chk = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND (ativo IS NULL OR ativo = 1)");
        $chk->execute([$toUserId]);
        if (!$chk->fetch()) {
            sendResponse(['success' => false, 'error' => 'Corretor de destino inválido'], 400);
        }

        if (is_array($leadIds) && count($leadIds) > 0) {
            // ownership check for non-admin
            if (!$isAdmin) {
                $ph = implode(',', array_fill(0, count($leadIds), '?'));
                $st = $pdo->prepare("SELECT id, corretor_id FROM leads WHERE id IN ($ph)");
                $st->execute($leadIds);
                foreach ($st->fetchAll() as $row) {
                    if ($isCorretor && ($row['corretor_id'] ?? '') !== $authId) {
                        sendResponse(['success' => false, 'error' => 'Você só pode direcionar seus próprios clientes'], 403);
                    }
                    if ($isCoord && ($row['corretor_id'] ?? '') !== $authId) {
                        $sub = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND coordenador_id = ?");
                        $sub->execute([$row['corretor_id'], $authId]);
                        if (!$sub->fetch()) {
                            sendResponse(['success' => false, 'error' => 'Lead fora da sua equipe'], 403);
                        }
                    }
                }
            }
            $ph = implode(',', array_fill(0, count($leadIds), '?'));
            $params = array_merge([$toUserId], $leadIds);
            $stmt = $pdo->prepare("UPDATE leads SET corretor_id = ?, data_att = NOW() WHERE id IN ($ph)");
            $stmt->execute($params);
        } else {
            if (!$isAdmin) {
                sendResponse(['success' => false, 'error' => 'Apenas Admin pode reatribuir em lote'], 403);
            }
            $stmt = $pdo->prepare("UPDATE leads SET corretor_id = ?, data_att = NOW() WHERE corretor_id = ?");
            $stmt->execute([$toUserId, $fromUserId]);
        }
        $moved = $stmt->rowCount();
        try {
            audit($pdo, $authId, 'reassign_leads', "to=$toUserId count=$moved");
        } catch (Exception $e) {}
        sendResponse(['success' => true, 'message' => "$moved lead(s) direcionado(s)", 'leadsTransferred' => $moved]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao direcionar: '.$e->getMessage()], 500);
    }
}

// ===============================
// 16. MÓDULO DE IMÓVEIS
// ===============================

if ($action === 'listImoveis') {
    $status     = $_GET['status'] ?? $data['status'] ?? '';
    $finalidade = $_GET['finalidade'] ?? $data['finalidade'] ?? '';
    $tipo       = $_GET['tipo'] ?? $data['tipo'] ?? '';
    $cidade     = $_GET['cidade'] ?? $data['cidade'] ?? '';
    $corretor   = $_GET['corretor'] ?? $data['corretor'] ?? '';
    $role       = $_GET['role'] ?? $data['role'] ?? '';
    $search     = $_GET['search'] ?? $data['search'] ?? '';
    $quartosMin = $_GET['quartosMin'] ?? $data['quartosMin'] ?? '';
    $valorMin   = $_GET['valorMin'] ?? $data['valorMin'] ?? '';
    $valorMax   = $_GET['valorMax'] ?? $data['valorMax'] ?? '';

    try {
        $sql = "SELECT i.*, u.nome AS corretor_nome, c.nome AS construtora_nome, e.nome AS empreendimento_nome
                FROM imoveis i
                LEFT JOIN usuarios u ON i.corretor_id = u.id
                LEFT JOIN construtoras c ON i.construtora_id = c.id
                LEFT JOIN empreendimentos e ON i.empreendimento_id = e.id
                WHERE i.ativo = 1";
        $params = [];

        if ($role !== 'Admin' && $role !== 'Root' && $role !== 'Coordenador' && $corretor !== '') {
            $sql .= " AND i.corretor_id = ?";
            $params[] = $corretor;
        }
        if (!empty($status)) { $sql .= " AND i.status = ?"; $params[] = $status; }
        if (!empty($finalidade)) { $sql .= " AND i.finalidade = ?"; $params[] = $finalidade; }
        if (!empty($tipo)) { $sql .= " AND i.tipo = ?"; $params[] = $tipo; }
        if (!empty($cidade)) { $sql .= " AND i.cidade = ?"; $params[] = $cidade; }
        if (!empty($quartosMin)) { $sql .= " AND i.quartos >= ?"; $params[] = (int)$quartosMin; }
        if (!empty($valorMin)) {
            $sql .= " AND (i.valor_venda >= ? OR i.valor_locacao >= ?)";
            $params[] = $valorMin; $params[] = $valorMin;
        }
        if (!empty($valorMax)) {
            $sql .= " AND ((i.valor_venda IS NOT NULL AND i.valor_venda <= ?) OR (i.valor_locacao IS NOT NULL AND i.valor_locacao <= ?))";
            $params[] = $valorMax; $params[] = $valorMax;
        }
        if (!empty($search)) {
            $sql .= " AND (i.titulo LIKE ? OR i.codigo LIKE ? OR i.bairro LIKE ? OR i.endereco LIKE ?)";
            $s = "%$search%";
            $params = array_merge($params, [$s, $s, $s, $s]);
        }

        $sql .= " ORDER BY i.atualizado_em DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $imoveis = $stmt->fetchAll();
        foreach ($imoveis as &$im) {
            $im['caracteristicas'] = $im['caracteristicas'] ? json_decode($im['caracteristicas'], true) : [];
        }
        sendResponse(['success' => true, 'imoveis' => $imoveis]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao listar imóveis: ' . $e->getMessage()], 500);
    }
}

if ($action === 'getImovel') {
    $id = $_GET['id'] ?? $data['id'] ?? null;
    if (!$id) sendResponse(['success' => false, 'error' => 'ID obrigatório'], 400);

    try {
        $stmt = $pdo->prepare(
            "SELECT i.*, u.nome AS corretor_nome, c.nome AS construtora_nome, e.nome AS empreendimento_nome
             FROM imoveis i
             LEFT JOIN usuarios u ON i.corretor_id = u.id
             LEFT JOIN construtoras c ON i.construtora_id = c.id
             LEFT JOIN empreendimentos e ON i.empreendimento_id = e.id
             WHERE i.id = ?"
        );
        $stmt->execute([$id]);
        $imovel = $stmt->fetch();
        if (!$imovel) sendResponse(['success' => false, 'error' => 'Imóvel não encontrado'], 404);

        $imovel['caracteristicas'] = $imovel['caracteristicas'] ? json_decode($imovel['caracteristicas'], true) : [];

        $fotoStmt = $pdo->prepare("SELECT * FROM imovel_fotos WHERE imovel_id = ? ORDER BY ordem, id");
        $fotoStmt->execute([$id]);
        $imovel['fotos'] = $fotoStmt->fetchAll();

        $leadStmt = $pdo->prepare(
            "SELECT li.*, l.nome AS lead_nome, l.tel, l.whatsapp, l.email, l.status AS lead_status
             FROM lead_imovel li JOIN leads l ON li.lead_id = l.id
             WHERE li.imovel_id = ? ORDER BY li.atualizado_em DESC"
        );
        $leadStmt->execute([$id]);
        $imovel['leads_interessados'] = $leadStmt->fetchAll();

        sendResponse(['success' => true, 'imovel' => $imovel]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao buscar imóvel: ' . $e->getMessage()], 500);
    }
}

if ($action === 'saveImovel') {
    $id = $data['id'] ?? null;
    $userId = $data['userId'] ?? null;
    $titulo = trim($data['titulo'] ?? '');
    if (empty($titulo)) sendResponse(['success' => false, 'error' => 'Título é obrigatório'], 400);

    $caracteristicas = isset($data['caracteristicas'])
        ? json_encode($data['caracteristicas'], JSON_UNESCAPED_UNICODE) : null;

    try {
        if (!empty($id)) {
            $stmt = $pdo->prepare(
                "UPDATE imoveis SET
                    titulo=?, tipo=?, finalidade=?, status=?,
                    endereco=?, numero=?, complemento=?, bairro=?, cidade=?, estado=?, cep=?,
                    valor_venda=?, valor_locacao=?, valor_condominio=?, valor_iptu=?,
                    area_total=?, area_util=?, quartos=?, suites=?, banheiros=?, vagas=?,
                    descricao=?, caracteristicas=?, tour_url=?, video_url=?,
                    corretor_id=?, construtora_id=?, empreendimento_id=?,
                    capa_url=?, observacoes_internas=?
                 WHERE id=?"
            );
            $stmt->execute([
                $titulo, $data['tipo'] ?? 'Apartamento', $data['finalidade'] ?? 'venda', $data['status'] ?? 'disponivel',
                $data['endereco'] ?? null, $data['numero'] ?? null, $data['complemento'] ?? null,
                $data['bairro'] ?? null, $data['cidade'] ?? null, $data['estado'] ?? null, $data['cep'] ?? null,
                $data['valorVenda'] ?? null, $data['valorLocacao'] ?? null, $data['valorCondominio'] ?? null, $data['valorIptu'] ?? null,
                $data['areaTotal'] ?? null, $data['areaUtil'] ?? null,
                $data['quartos'] ?? 0, $data['suites'] ?? 0, $data['banheiros'] ?? 0, $data['vagas'] ?? 0,
                $data['descricao'] ?? null, $caracteristicas, $data['tourUrl'] ?? null, $data['videoUrl'] ?? null,
                $data['corretorId'] ?? $userId, $data['construtoraId'] ?? null, $data['empreendimentoId'] ?? null,
                $data['capaUrl'] ?? null, $data['observacoesInternas'] ?? null, $id
            ]);
            try {
                $pdo->prepare("INSERT INTO imovel_historico (imovel_id, usuario_id, acao, nota) VALUES (?,?, 'atualizado', ?)")
                    ->execute([$id, $userId, $data['notaHistorico'] ?? 'Imóvel atualizado']);
            } catch (Exception $e) {}
            try {
                $slug = trim($data['slug'] ?? '');
                if ($slug === '') $slug = slugifyImovel($titulo);
                $diferenciaisJson = isset($data['diferenciais'])
                    ? (is_string($data['diferenciais']) ? $data['diferenciais'] : json_encode($data['diferenciais'], JSON_UNESCAPED_UNICODE))
                    : null;
                $pdo->prepare(
                    "UPDATE imoveis SET
                        slug = ?, categoria_site = ?, badge = ?, badge_class = ?,
                        condicao = ?, preco_texto = ?, area_texto = ?, diferenciais = ?,
                        publicado = ?, destaque = ?
                     WHERE id = ?"
                )->execute([
                    $slug,
                    $data['categoria'] ?? $data['categoria_site'] ?? 'prontos',
                    $data['badge'] ?? null,
                    $data['badgeClass'] ?? $data['badge_class'] ?? null,
                    $data['condicao'] ?? null,
                    $data['preco'] ?? $data['preco_texto'] ?? null,
                    $data['area'] ?? $data['area_texto'] ?? null,
                    $diferenciaisJson,
                    isset($data['publicado']) ? (int)!!$data['publicado'] : 1,
                    isset($data['destaque']) ? (int)!!$data['destaque'] : 0,
                    $id
                ]);
            } catch (Exception $e) {}
            audit($pdo, $userId, 'update_imovel', "Imóvel ID: $id");
            sendResponse(['success' => true, 'message' => 'Imóvel atualizado', 'id' => $id, 'slug' => ($slug ?? null)]);
        } else {
            $ano = date('Y');
            $countStmt = $pdo->query("SELECT COUNT(*) + 1 AS prox FROM imoveis WHERE YEAR(criado_em) = YEAR(NOW())");
            $prox = $countStmt->fetch()['prox'];
            $codigo = sprintf('IMV-%s-%04d', $ano, $prox);

            $stmt = $pdo->prepare(
                "INSERT INTO imoveis (
                    codigo, titulo, tipo, finalidade, status,
                    endereco, numero, complemento, bairro, cidade, estado, cep,
                    valor_venda, valor_locacao, valor_condominio, valor_iptu,
                    area_total, area_util, quartos, suites, banheiros, vagas,
                    descricao, caracteristicas, tour_url, video_url,
                    corretor_id, construtora_id, empreendimento_id, capa_url, observacoes_internas
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->execute([
                $codigo, $titulo, $data['tipo'] ?? 'Apartamento', $data['finalidade'] ?? 'venda', $data['status'] ?? 'disponivel',
                $data['endereco'] ?? null, $data['numero'] ?? null, $data['complemento'] ?? null,
                $data['bairro'] ?? null, $data['cidade'] ?? null, $data['estado'] ?? null, $data['cep'] ?? null,
                $data['valorVenda'] ?? null, $data['valorLocacao'] ?? null, $data['valorCondominio'] ?? null, $data['valorIptu'] ?? null,
                $data['areaTotal'] ?? null, $data['areaUtil'] ?? null,
                $data['quartos'] ?? 0, $data['suites'] ?? 0, $data['banheiros'] ?? 0, $data['vagas'] ?? 0,
                $data['descricao'] ?? null, $caracteristicas, $data['tourUrl'] ?? null, $data['videoUrl'] ?? null,
                $data['corretorId'] ?? $userId, $data['construtoraId'] ?? null, $data['empreendimentoId'] ?? null,
                $data['capaUrl'] ?? null, $data['observacoesInternas'] ?? null
            ]);
            $newId = $pdo->lastInsertId();
            try {
                $pdo->prepare("INSERT INTO imovel_historico (imovel_id, usuario_id, acao, nota) VALUES (?,?, 'criado', 'Imóvel cadastrado')")
                    ->execute([$newId, $userId]);
            } catch (Exception $e) {}
            
            try {
                $slug = trim($data['slug'] ?? '');
                if ($slug === '') $slug = slugifyImovel($titulo);
                $diferenciaisJson = isset($data['diferenciais'])
                    ? (is_string($data['diferenciais']) ? $data['diferenciais'] : json_encode($data['diferenciais'], JSON_UNESCAPED_UNICODE))
                    : null;
                $pdo->prepare(
                    "UPDATE imoveis SET
                        slug = ?, categoria_site = ?, badge = ?, badge_class = ?,
                        condicao = ?, preco_texto = ?, area_texto = ?, diferenciais = ?,
                        publicado = ?, destaque = ?
                     WHERE id = ?"
                )->execute([
                    $slug,
                    $data['categoria'] ?? $data['categoria_site'] ?? 'prontos',
                    $data['badge'] ?? null,
                    $data['badgeClass'] ?? $data['badge_class'] ?? null,
                    $data['condicao'] ?? null,
                    $data['preco'] ?? $data['preco_texto'] ?? null,
                    $data['area'] ?? $data['area_texto'] ?? null,
                    $diferenciaisJson,
                    isset($data['publicado']) ? (int)!!$data['publicado'] : 1,
                    isset($data['destaque']) ? (int)!!$data['destaque'] : 0,
                    $newId
                ]);
            } catch (Exception $e) {}

            audit($pdo, $userId, 'create_imovel', "Imóvel: $codigo - $titulo");
            sendResponse(['success' => true, 'message' => 'Imóvel cadastrado', 'id' => $newId, 'codigo' => $codigo, 'slug' => ($slug ?? null)]);
        }
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao salvar imóvel: ' . $e->getMessage()], 500);
    }
}

if ($action === 'updateImovelStatus') {
    $id = $data['id'] ?? null;
    $status = $data['status'] ?? null;
    $userId = $data['userId'] ?? null;
    if (!$id || !$status) sendResponse(['success' => false, 'error' => 'ID e status são obrigatórios'], 400);
    $valid = ['disponivel','reservado','vendido','alugado','inativo'];
    if (!in_array($status, $valid)) sendResponse(['success' => false, 'error' => 'Status inválido'], 400);

    try {
        $old = $pdo->prepare("SELECT status FROM imoveis WHERE id = ?");
        $old->execute([$id]);
        $anterior = $old->fetchColumn();
        $pdo->prepare("UPDATE imoveis SET status = ? WHERE id = ?")->execute([$status, $id]);
        try {
            $pdo->prepare("INSERT INTO imovel_historico (imovel_id, usuario_id, acao, campo, valor_anterior, valor_novo) VALUES (?,?, 'status_alterado', 'status', ?, ?)")
                ->execute([$id, $userId, $anterior, $status]);
        } catch (Exception $e) {}
        audit($pdo, $userId, 'update_imovel_status', "Imóvel $id: $anterior → $status");
        sendResponse(['success' => true, 'message' => 'Status atualizado']);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao atualizar status'], 500);
    }
}

if ($action === 'deleteImovel') {
    $role = $data['role'] ?? '';
    requireAdmin($role);
    $id = $data['id'] ?? null;
    if (!$id) sendResponse(['success' => false, 'error' => 'ID obrigatório'], 400);
    try {
        $pdo->prepare("UPDATE imoveis SET ativo = 0, status = 'inativo' WHERE id = ?")->execute([$id]);
        audit($pdo, $data['userId'] ?? null, 'delete_imovel', "Imóvel ID: $id");
        sendResponse(['success' => true, 'message' => 'Imóvel removido']);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao remover imóvel'], 500);
    }
}

if ($action === 'vincularLeadImovel') {
    $leadId = $data['leadId'] ?? null;
    $imovelId = $data['imovelId'] ?? null;
    $interesse = $data['interesse'] ?? 'compra';
    $nota = $data['nota'] ?? null;
    $userId = $data['userId'] ?? null;
    if (!$leadId || !$imovelId) sendResponse(['success' => false, 'error' => 'Lead e Imóvel são obrigatórios'], 400);

    try {
        $pdo->prepare(
            "INSERT INTO lead_imovel (lead_id, imovel_id, interesse, nota)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE interesse = VALUES(interesse), nota = VALUES(nota), atualizado_em = NOW()"
        )->execute([$leadId, $imovelId, $interesse, $nota]);

        try {
            $pdo->prepare("INSERT INTO historico (lead_id, data, status, nota, usuario_id) VALUES (?, NOW(), ?, ?, ?)")
                ->execute([$leadId, 'imovel_vinculado', "Imóvel #$imovelId vinculado ($interesse)", $userId]);
        } catch (Exception $e) {}

        audit($pdo, $userId, 'vincular_lead_imovel', "Lead $leadId ↔ Imóvel $imovelId");
        sendResponse(['success' => true, 'message' => 'Lead vinculado ao imóvel']);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao vincular: ' . $e->getMessage()], 500);
    }
}

if ($action === 'desvincularLeadImovel') {
    $leadId = $data['leadId'] ?? null;
    $imovelId = $data['imovelId'] ?? null;
    if (!$leadId || !$imovelId) sendResponse(['success' => false, 'error' => 'Lead e Imóvel são obrigatórios'], 400);
    try {
        $pdo->prepare("DELETE FROM lead_imovel WHERE lead_id = ? AND imovel_id = ?")->execute([$leadId, $imovelId]);
        sendResponse(['success' => true, 'message' => 'Vínculo removido']);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao desvincular'], 500);
    }
}

if ($action === 'listImoveisDoLead') {
    $leadId = $_GET['leadId'] ?? $data['leadId'] ?? null;
    if (!$leadId) sendResponse(['success' => false, 'error' => 'leadId obrigatório'], 400);
    try {
        $stmt = $pdo->prepare(
            "SELECT i.*, li.interesse, li.status_interesse, li.nota AS nota_vinculo, li.criado_em AS vinculado_em
             FROM lead_imovel li JOIN imoveis i ON li.imovel_id = i.id
             WHERE li.lead_id = ? AND i.ativo = 1 ORDER BY li.atualizado_em DESC"
        );
        $stmt->execute([$leadId]);
        $imoveis = $stmt->fetchAll();
        foreach ($imoveis as &$im) {
            $im['caracteristicas'] = $im['caracteristicas'] ? json_decode($im['caracteristicas'], true) : [];
        }
        sendResponse(['success' => true, 'imoveis' => $imoveis]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao listar imóveis do lead'], 500);
    }
}

if ($action === 'statsImoveis') {
    $corretor = $_GET['corretor'] ?? $data['corretor'] ?? '';
    $role = $_GET['role'] ?? $data['role'] ?? '';
    try {
        $where = "WHERE ativo = 1";
        $params = [];
        if ($role !== 'Admin' && $role !== 'Root' && $role !== 'Coordenador' && $corretor !== '') {
            $where .= " AND corretor_id = ?";
            $params[] = $corretor;
        }
        $stmt = $pdo->prepare("SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'disponivel' AND finalidade IN ('venda','venda_locacao') THEN 1 ELSE 0 END) AS disponiveis_venda,
            SUM(CASE WHEN status = 'disponivel' AND finalidade IN ('locacao','venda_locacao') THEN 1 ELSE 0 END) AS disponiveis_locacao,
            SUM(CASE WHEN status = 'vendido' THEN 1 ELSE 0 END) AS vendidos,
            SUM(CASE WHEN status = 'alugado' THEN 1 ELSE 0 END) AS alugados,
            SUM(CASE WHEN status = 'reservado' THEN 1 ELSE 0 END) AS reservados,
            COALESCE(SUM(CASE WHEN status = 'disponivel' THEN valor_venda ELSE 0 END),0) AS valor_carteira_venda,
            COALESCE(SUM(CASE WHEN status = 'disponivel' THEN valor_locacao ELSE 0 END),0) AS valor_carteira_locacao
            FROM imoveis $where");
        $stmt->execute($params);
        sendResponse(['success' => true, 'stats' => $stmt->fetch()]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao obter estatísticas de imóveis'], 500);
    }
}


// ===============================
// 17. UPLOAD DE FOTOS DE IMÓVEIS
// ===============================

function imovelUploadDir()
{
    $dir = __DIR__ . '/uploads/imoveis';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function imovelUploadPublicBase()
{
    // URL relativa ao api.php (ajuste se a API estiver em subpasta)
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($scriptDir === '/' || $scriptDir === '') {
        return '/uploads/imoveis';
    }
    return $scriptDir . '/uploads/imoveis';
}

function validateImageUpload($file)
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return 'Upload inválido';
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $map = [
            UPLOAD_ERR_INI_SIZE => 'Arquivo excede o limite do servidor',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande',
            UPLOAD_ERR_PARTIAL => 'Upload incompleto',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
        ];
        return $map[$file['error']] ?? 'Erro no upload';
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return 'Máximo 5MB por foto';
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($allowed[$mime])) {
        return 'Formato não permitido (use JPG, PNG, WEBP ou GIF)';
    }
    return ['mime' => $mime, 'ext' => $allowed[$mime]];
}

if ($action === 'uploadImovelFoto') {
    $imovelId = $_POST['imovelId'] ?? $_POST['imovel_id'] ?? null;
    $userId = $_POST['userId'] ?? null;
    $isCapa = isset($_POST['isCapa']) ? (int)!!$_POST['isCapa'] : 0;

    if (!$imovelId) {
        sendResponse(['success' => false, 'error' => 'imovelId é obrigatório'], 400);
    }
    if (!isset($_FILES['foto']) && !isset($_FILES['file'])) {
        sendResponse(['success' => false, 'error' => 'Nenhuma foto enviada (campo foto)'], 400);
    }
    $file = $_FILES['foto'] ?? $_FILES['file'];

    try {
        $chk = $pdo->prepare("SELECT id FROM imoveis WHERE id = ? AND ativo = 1");
        $chk->execute([$imovelId]);
        if (!$chk->fetch()) {
            sendResponse(['success' => false, 'error' => 'Imóvel não encontrado'], 404);
        }

        $valid = validateImageUpload($file);
        if (is_string($valid)) {
            sendResponse(['success' => false, 'error' => $valid], 400);
        }

        $dir = imovelUploadDir();
        $subdir = $dir . '/' . (int)$imovelId;
        if (!is_dir($subdir)) {
            @mkdir($subdir, 0755, true);
        }

        $name = 'img_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $valid['ext'];
        $dest = $subdir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            sendResponse(['success' => false, 'error' => 'Falha ao salvar arquivo no servidor'], 500);
        }

        $publicUrl = imovelUploadPublicBase() . '/' . (int)$imovelId . '/' . $name;

        // ordem
        $ordStmt = $pdo->prepare("SELECT COALESCE(MAX(ordem),0)+1 AS prox FROM imovel_fotos WHERE imovel_id = ?");
        $ordStmt->execute([$imovelId]);
        $ordem = (int)$ordStmt->fetch()['prox'];

        if ($isCapa) {
            $pdo->prepare("UPDATE imovel_fotos SET is_capa = 0 WHERE imovel_id = ?")->execute([$imovelId]);
            $pdo->prepare("UPDATE imoveis SET capa_url = ? WHERE id = ?")->execute([$publicUrl, $imovelId]);
        }

        $ins = $pdo->prepare(
            "INSERT INTO imovel_fotos (imovel_id, url, ordem, is_capa) VALUES (?,?,?,?)"
        );
        $ins->execute([$imovelId, $publicUrl, $ordem, $isCapa ? 1 : 0]);
        $fotoId = $pdo->lastInsertId();

        // Se não há capa ainda, usa esta
        if (!$isCapa) {
            $cap = $pdo->prepare("SELECT capa_url FROM imoveis WHERE id = ?");
            $cap->execute([$imovelId]);
            $capa = $cap->fetchColumn();
            if (empty($capa)) {
                $pdo->prepare("UPDATE imoveis SET capa_url = ? WHERE id = ?")->execute([$publicUrl, $imovelId]);
                $pdo->prepare("UPDATE imovel_fotos SET is_capa = 1 WHERE id = ?")->execute([$fotoId]);
                $isCapa = 1;
            }
        }

        audit($pdo, $userId, 'upload_imovel_foto', "Imóvel $imovelId foto $fotoId");
        sendResponse([
            'success' => true,
            'message' => 'Foto enviada',
            'foto' => [
                'id' => (int)$fotoId,
                'imovel_id' => (int)$imovelId,
                'url' => $publicUrl,
                'ordem' => $ordem,
                'is_capa' => (int)$isCapa
            ]
        ]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro no upload: ' . $e->getMessage()], 500);
    }
}

if ($action === 'deleteImovelFoto') {
    $fotoId = $data['fotoId'] ?? $data['id'] ?? null;
    $userId = $data['userId'] ?? null;
    if (!$fotoId) {
        sendResponse(['success' => false, 'error' => 'fotoId obrigatório'], 400);
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM imovel_fotos WHERE id = ?");
        $stmt->execute([$fotoId]);
        $foto = $stmt->fetch();
        if (!$foto) {
            sendResponse(['success' => false, 'error' => 'Foto não encontrada'], 404);
        }

        $pdo->prepare("DELETE FROM imovel_fotos WHERE id = ?")->execute([$fotoId]);

        // Remove arquivo físico se estiver na pasta local de uploads
        $url = $foto['url'] ?? '';
        if (strpos($url, '/uploads/imoveis/') !== false) {
            $rel = substr($url, strpos($url, '/uploads/imoveis/'));
            $path = __DIR__ . $rel;
            // também tenta path relativo
            if (!is_file($path)) {
                $path = __DIR__ . '/uploads/imoveis/' . basename(dirname($url)) . '/' . basename($url);
            }
            if (is_file($path)) {
                @unlink($path);
            }
        }

        // Se era capa, promove outra ou limpa
        if (!empty($foto['is_capa'])) {
            $next = $pdo->prepare("SELECT id, url FROM imovel_fotos WHERE imovel_id = ? ORDER BY ordem, id LIMIT 1");
            $next->execute([$foto['imovel_id']]);
            $n = $next->fetch();
            if ($n) {
                $pdo->prepare("UPDATE imovel_fotos SET is_capa = 1 WHERE id = ?")->execute([$n['id']]);
                $pdo->prepare("UPDATE imoveis SET capa_url = ? WHERE id = ?")->execute([$n['url'], $foto['imovel_id']]);
            } else {
                $pdo->prepare("UPDATE imoveis SET capa_url = NULL WHERE id = ?")->execute([$foto['imovel_id']]);
            }
        }

        audit($pdo, $userId, 'delete_imovel_foto', "Foto $fotoId");
        sendResponse(['success' => true, 'message' => 'Foto removida']);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao remover foto'], 500);
    }
}

if ($action === 'setImovelCapa') {
    $fotoId = $data['fotoId'] ?? null;
    $imovelId = $data['imovelId'] ?? null;
    $userId = $data['userId'] ?? null;
    if (!$fotoId || !$imovelId) {
        sendResponse(['success' => false, 'error' => 'fotoId e imovelId obrigatórios'], 400);
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM imovel_fotos WHERE id = ? AND imovel_id = ?");
        $stmt->execute([$fotoId, $imovelId]);
        $foto = $stmt->fetch();
        if (!$foto) {
            sendResponse(['success' => false, 'error' => 'Foto não encontrada'], 404);
        }

        $pdo->prepare("UPDATE imovel_fotos SET is_capa = 0 WHERE imovel_id = ?")->execute([$imovelId]);
        $pdo->prepare("UPDATE imovel_fotos SET is_capa = 1 WHERE id = ?")->execute([$fotoId]);
        $pdo->prepare("UPDATE imoveis SET capa_url = ? WHERE id = ?")->execute([$foto['url'], $imovelId]);

        audit($pdo, $userId, 'set_imovel_capa', "Imóvel $imovelId foto $fotoId");
        sendResponse(['success' => true, 'message' => 'Capa atualizada', 'capaUrl' => $foto['url']]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => 'Erro ao definir capa'], 500);
    }
}



// ===============================
// 18. CATÁLOGO PÚBLICO (SITE vinutto.com)
// Unifica CRM ↔ site: listagem sem login para o front público
// ===============================

function slugifyImovel($text)
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $text));
    return trim($text, '-') ?: ('imovel-' . time());
}

function formatImovelPublico(array $row, array $fotos = [])
{
    $preco = null;
    if (!empty($row['preco_texto'])) {
        $preco = $row['preco_texto'];
    } elseif (!empty($row['valor_venda'])) {
        $preco = 'R$ ' . number_format((float)$row['valor_venda'], 2, ',', '.');
    } elseif (!empty($row['valor_locacao'])) {
        $preco = 'R$ ' . number_format((float)$row['valor_locacao'], 2, ',', '.') . '/mês';
    } else {
        $preco = 'Consulte';
    }

    $area = $row['area_texto'] ?? null;
    if (!$area && !empty($row['area_util'])) {
        $area = rtrim(rtrim(number_format((float)$row['area_util'], 2, ',', '.'), '0'), ',') . 'm²';
    }

    $diferenciais = [];
    if (!empty($row['diferenciais'])) {
        $d = json_decode($row['diferenciais'], true);
        if (is_array($d)) $diferenciais = $d;
    } elseif (!empty($row['caracteristicas'])) {
        $c = is_string($row['caracteristicas']) ? json_decode($row['caracteristicas'], true) : $row['caracteristicas'];
        if (is_array($c)) $diferenciais = array_values(array_filter($c, 'is_string'));
    }

    $fotoUrls = [];
    foreach ($fotos as $f) {
        if (!empty($f['url'])) $fotoUrls[] = $f['url'];
    }
    if (!$fotoUrls && !empty($row['capa_url'])) {
        $fotoUrls[] = $row['capa_url'];
    }

    $slug = $row['slug'] ?? null;
    if (!$slug) {
        $slug = !empty($row['codigo']) ? strtolower(preg_replace('/[^a-z0-9]+/i', '-', $row['codigo'])) : ('imv-' . $row['id']);
    }

    $categoria = $row['categoria_site'] ?? 'prontos';
    if (!in_array($categoria, ['prontos', 'lancamentos'], true)) {
        $categoria = 'prontos';
    }

    return [
        'id' => $slug,
        'crmId' => (int)$row['id'],
        'codigo' => $row['codigo'] ?? null,
        'titulo' => $row['titulo'] ?? '',
        'preco' => $preco,
        'condicao' => $row['condicao'] ?? '',
        'area' => $area ?: '',
        'quartos' => isset($row['quartos']) ? (string)$row['quartos'] : '',
        'vagas' => isset($row['vagas']) ? (string)$row['vagas'] : '',
        'bairro' => $row['bairro'] ?? ($row['cidade'] ?? ''),
        'badge' => $row['badge'] ?: null,
        'badgeClass' => $row['badge_class'] ?? null,
        'diferenciais' => $diferenciais,
        'descricao' => $row['descricao'] ?? '',
        'fotos' => $fotoUrls,
        'categoria' => $categoria,
        'link' => 'https://vinutto.com/imovel.html?id=' . rawurlencode($slug),
        'tipo' => $row['tipo'] ?? null,
        'finalidade' => $row['finalidade'] ?? null,
        'status' => $row['status'] ?? null,
        'cidade' => $row['cidade'] ?? null,
        'estado' => $row['estado'] ?? null,
        'capa' => $row['capa_url'] ?? ($fotoUrls[0] ?? null),
    ];
}

if ($action === 'listImoveisPublico') {
    $categoria = $_GET['categoria'] ?? $data['categoria'] ?? '';
    $bairro = $_GET['bairro'] ?? $data['bairro'] ?? '';
    $search = $_GET['search'] ?? $data['search'] ?? '';
    try {
        // Publicado no site + ativo + disponível/reservado (não vende/inativo oculto)
        $sql = "SELECT * FROM imoveis
                WHERE ativo = 1
                  AND COALESCE(publicado, 0) = 1
                  AND status IN ('disponivel','reservado')";
        $params = [];
        if ($categoria !== '') {
            $sql .= " AND COALESCE(categoria_site, 'prontos') = ?";
            $params[] = $categoria;
        }
        if ($bairro !== '') {
            $sql .= " AND bairro LIKE ?";
            $params[] = '%' . $bairro . '%';
        }
        if ($search !== '') {
            $sql .= " AND (titulo LIKE ? OR bairro LIKE ? OR cidade LIKE ? OR descricao LIKE ?)";
            $s = '%' . $search . '%';
            array_push($params, $s, $s, $s, $s);
        }
        $sql .= " ORDER BY COALESCE(destaque,0) DESC, atualizado_em DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $ids = array_column($rows, 'id');
        $fotosPorImovel = [];
        if ($ids) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $fstmt = $pdo->prepare("SELECT * FROM imovel_fotos WHERE imovel_id IN ($ph) ORDER BY is_capa DESC, ordem, id");
            $fstmt->execute($ids);
            foreach ($fstmt->fetchAll() as $f) {
                $fotosPorImovel[$f['imovel_id']][] = $f;
            }
        }

        $prontos = [];
        $lancamentos = [];
        $lista = [];
        foreach ($rows as $row) {
            $item = formatImovelPublico($row, $fotosPorImovel[$row['id']] ?? []);
            $lista[] = $item;
            if ($item['categoria'] === 'lancamentos') $lancamentos[] = $item;
            else $prontos[] = $item;
        }

        sendResponse([
            'success' => true,
            'IMOVEIS' => ['prontos' => $prontos, 'lancamentos' => $lancamentos],
            'imoveis' => $lista,
            'total' => count($lista)
        ]);
    } catch (Exception $e) {
        // Se colunas ainda não existem, tenta query mínima
        try {
            $stmt = $pdo->query("SELECT * FROM imoveis WHERE ativo = 1 AND status = 'disponivel' ORDER BY id DESC LIMIT 100");
            $rows = $stmt->fetchAll();
            $lista = [];
            foreach ($rows as $row) {
                $lista[] = formatImovelPublico($row, []);
            }
            sendResponse([
                'success' => true,
                'IMOVEIS' => [
                    'prontos' => array_values(array_filter($lista, fn($i) => ($i['categoria'] ?? 'prontos') !== 'lancamentos')),
                    'lancamentos' => array_values(array_filter($lista, fn($i) => ($i['categoria'] ?? '') === 'lancamentos')),
                ],
                'imoveis' => $lista,
                'total' => count($lista),
                'warning' => 'Execute a migração SQL de unificação (colunas publicado/slug/etc.)'
            ]);
        } catch (Exception $e2) {
            sendResponse(['success' => false, 'error' => 'Erro catálogo público: ' . $e->getMessage()], 500);
        }
    }
}

if ($action === 'getImovelPublico') {
    $slug = $_GET['slug'] ?? $_GET['id'] ?? $data['slug'] ?? $data['id'] ?? null;
    if (!$slug) sendResponse(['success' => false, 'error' => 'id/slug obrigatório'], 400);
    try {
        $stmt = $pdo->prepare(
            "SELECT * FROM imoveis
             WHERE ativo = 1 AND COALESCE(publicado, 0) = 1
               AND (slug = ? OR codigo = ? OR id = ?)
             LIMIT 1"
        );
        $stmt->execute([$slug, $slug, is_numeric($slug) ? (int)$slug : 0]);
        $row = $stmt->fetch();
        if (!$row) sendResponse(['success' => false, 'error' => 'Imóvel não encontrado'], 404);

        $fotoStmt = $pdo->prepare("SELECT * FROM imovel_fotos WHERE imovel_id = ? ORDER BY is_capa DESC, ordem, id");
        $fotoStmt->execute([$row['id']]);
        $fotos = $fotoStmt->fetchAll();
        sendResponse(['success' => true, 'imovel' => formatImovelPublico($row, $fotos)]);
    } catch (Exception $e) {
        sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}


// ===============================
// AÇÃO INVÁLIDA
// ===============================
sendResponse(['error' => 'Ação inválida ou não especificada'], 400);