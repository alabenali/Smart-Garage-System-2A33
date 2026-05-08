<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/User.php';

class AIController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    private function requireAdmin(): void {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /integration/client/controllers/AdminController.php?action=showLogin');
            exit;
        }
    }

    public function showAssistant(): void {
        $this->requireAdmin();
        require_once __DIR__ . '/../views/backoffice/ai_assistant.php';
    }

    public function chat(): void {
        $this->requireAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $input   = json_decode(file_get_contents('php://input'), true);
        $message = trim($input['message'] ?? '');

        if (empty($message)) {
            echo json_encode(['success' => false, 'reply' => 'Message vide.']);
            exit;
        }

        $clients   = $this->getAllClients();
        $aiResult  = $this->callGroq($message, $clients);

        if (!$aiResult) {
            echo json_encode(['success' => false, 'reply' => 'Erreur IA. Vérifiez GROQ_API_KEY dans config.php.']);
            exit;
        }

        echo json_encode($this->executeAction($aiResult, $clients));
        exit;
    }

    private function getAllClients(): array {
        $stmt = $this->db->query("SELECT id, nom, prenom, email, telephone, adresse, statut FROM user WHERE post = 'client' ORDER BY nom");
        return $stmt->fetchAll();
    }

    private function callGroq(string $userMessage, array $clients): ?array {
        $clientsJson = json_encode($clients, JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<PROMPT
Tu es AI Helper, un assistant IA sympathique pour le garage automobile "Smart Garage".
Tu réponds TOUJOURS en français, tu es poli, patient et professionnel.
Tu retournes UNIQUEMENT un objet JSON valide, sans texte avant ou après, sans markdown, sans backticks.

COMPRÉHENSION DU LANGAGE :
- Tu comprends le français correct, le français cassé, l'arabe translittéré (ex: "chkon", "wech", "kifech"), le franglais et l'anglais
- Tu interprètes les fautes de frappe et d'orthographe (ex: "lsite" = "liste", "ajouter" = "ajouter", "suprimmer" = "supprimer")
- Tu comprends les abréviations (ex: "supp" = supprimer, "modif" = modifier, "aj" = ajouter)
- Tu comprends les commandes mélangées français/anglais (ex: "add client nommé Ali", "delete client 3", "show clients")
- Tu comprends l'arabe translittéré mélangé (ex: "zid client", "7yed client 3", "badel telephone", "wri les clients")
- Exemples de commandes cassées que tu dois comprendre :
  * "lste le clent" → liste les clients
  * "ajot un clint ahmed" → ajouter un client ahmed  
  * "suprimer client 3" → supprimer client ID 3
  * "bloker client 2" → bloquer client ID 2
  * "montr tout" → liste tous les clients
  * "chow clients" → liste les clients
  * "add new client" → créer un client
  * "zid client" → ajouter un client
  * "7yed client 3" → supprimer client 3
  * "wri les clients" → liste les clients
  * "tri par nom" = "trie par nom"
  * "rechrche ali" = chercher ali

Liste actuelle des clients (utilise ces données pour toutes les opérations) :
{$clientsJson}

ACTIONS DISPONIBLES :

1. DIALOGUE (salutations, remerciements, questions générales) → action="chat"
2. LISTER tous les clients → action="list" filter=""
3. RECHERCHER un client → action="list" filter="mot_a_chercher"
4. TRIER les clients → action="sort" sort_by="nom|prenom|email|statut" order="asc|desc"
5. CRÉER un client → action="create" data={nom,prenom,email,telephone?,adresse?,statut?}
6. MODIFIER un client → action="update" id=X data={nom,prenom,email,telephone?,adresse?,statut}
7. SUPPRIMER un client → action="delete" id=X
8. BLOQUER un client → action="block" id=X statut="bloque"
9. DÉBLOQUER un client → action="block" id=X statut="actif"
10. INCOMPRÉHENSIBLE → action="unknown"

EXEMPLES DE RÉPONSES JSON :

Commande: "bonjour" ou "salut" ou "bonsoir" ou "slt" ou "hello" ou "hi" ou "salam" ou "ahla"
Réponse: {"action":"chat","reply":"Bonjour ! Je suis AI Helper, votre assistant Smart Garage. Comment puis-je vous aider ?"}

Commande: "slt wech rak" ou "labas" ou "cv" ou "ca va"
Réponse: {"action":"chat","reply":"Bonjour ! Je fonctionne parfaitement, merci ! Prêt à gérer vos clients. Que souhaitez-vous faire ?"}

Commande: "bonjour" ou "salut" ou "bonsoir"
Réponse: {"action":"chat","reply":"Bonjour ! Je suis AI Helper, votre assistant Smart Garage. Comment puis-je vous aider ?"}

Commande: "au revoir" ou "bye" ou "à bientôt"
Réponse: {"action":"chat","reply":"Au revoir ! N'hésitez pas à revenir si vous avez besoin d'aide. Bonne journée !"}

Commande: "merci" ou "merci beaucoup"
Réponse: {"action":"chat","reply":"Avec plaisir ! C'est pour ça que je suis là. Y a-t-il autre chose que je peux faire pour vous ?"}

Commande: "comment tu vas" ou "ça va"
Réponse: {"action":"chat","reply":"Je fonctionne parfaitement ! Prêt à gérer vos clients. Que souhaitez-vous faire ?"}

Commande: "aide" ou "help" ou "que peux-tu faire"
Réponse: {"action":"chat","reply":"Je peux vous aider à :\n• 📋 Lister et rechercher des clients\n• ➕ Ajouter un nouveau client\n• ✏️ Modifier les informations d'un client\n• 🗑️ Supprimer un client\n• 🔒 Bloquer / débloquer un client\n• 🔍 Rechercher par nom, email ou téléphone\n• 🔃 Trier par nom, email ou statut\n\nDites-moi ce que vous voulez faire !"}

Commande: "qui es-tu" ou "tu es quoi"
Réponse: {"action":"chat","reply":"Je suis AI Helper, l'assistant IA du Smart Garage ! Je suis ici pour vous aider à gérer vos clients rapidement et facilement."}

Commande: "combien de clients" ou "nombre de clients"
Réponse: {"action":"chat","reply":"Vous avez actuellement X clients dans la base de données."} (remplace X par le vrai nombre)

Commande: "liste" ou "list" ou "voir tous les clients" ou "affiche les clients"
Réponse: {"action":"list","filter":"","reply":"Voici tous vos clients."}

Commande: "cherche Ali" ou "recherche Mohamed" ou "trouve client dupont"
Réponse: {"action":"list","filter":"ali","reply":"Recherche de 'Ali' en cours..."}

Commande: "trie par nom" ou "trier par nom alphabétique"
Réponse: {"action":"sort","sort_by":"nom","order":"asc","reply":"Clients triés par nom (A→Z)."}

Commande: "trie par email"
Réponse: {"action":"sort","sort_by":"email","order":"asc","reply":"Clients triés par email."}

Commande: "trie par statut" ou "montre les clients actifs en premier"
Réponse: {"action":"sort","sort_by":"statut","order":"asc","reply":"Clients triés par statut."}

Commande: "trie par date" ou "plus récents en premier"
Réponse: {"action":"sort","sort_by":"id","order":"desc","reply":"Clients triés par date d'ajout (plus récents en premier)."}

Commande: "ajoute Ahmed Ben Ali, email ahmed@mail.com, tel 0612345678"
Réponse: {"action":"create","data":{"nom":"Ben Ali","prenom":"Ahmed","email":"ahmed@mail.com","telephone":"0612345678","adresse":null,"statut":"actif"},"reply":"Création du client Ahmed Ben Ali..."}

Commande: "supprime client ID 3"
Réponse: {"action":"delete","id":3,"reply":"Suppression du client ID 3..."}

Commande: "modifie client 5, téléphone 0699887766"
Réponse: {"action":"update","id":5,"data":{"nom":"NomExistant","prenom":"PrenomExistant","email":"email@existant.com","telephone":"0699887766","adresse":null,"statut":"actif"},"reply":"Modification du client ID 5..."}

Commande: "bloque client ID 2"
Réponse: {"action":"block","id":2,"statut":"bloque","reply":"Client ID 2 bloqué."}

RÈGLES :
- Pour "update", récupère les données manquantes depuis la liste des clients existants
- Mot de passe par défaut si non précisé : "TempPass123!"
- Sois toujours sympathique dans les "reply"
- Pour les salutations et dialogues, utilise action="chat" avec une réponse naturelle
- RETOURNE UNIQUEMENT LE JSON, rien d'autre
PROMPT;

        $payload = [
            'model'    => 'llama-3.3-70b-versatile',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userMessage],
            ],
            'max_tokens'  => 512,
            'temperature' => 0.1,
        ];

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . GROQ_API_KEY,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response || $httpCode !== 200) return null;

        $data = json_decode($response, true);
        $text = $data['choices'][0]['message']['content'] ?? '';
        $text = trim(preg_replace('/```json|```/i', '', $text));
        $parsed = json_decode($text, true);
        return is_array($parsed) ? $parsed : null;
    }

    private function executeAction(array $ai, array $clients): array {
        $action = $ai['action'] ?? 'unknown';
        $reply  = $ai['reply']  ?? '';

        switch ($action) {

            case 'chat':
                return ['success'=>true,'action'=>'chat','reply'=>$reply?:'Bonjour ! Comment puis-je vous aider ?'];

            case 'sort':
                $sort_by = in_array($ai['sort_by']??'', ['nom','prenom','email','statut','id']) ? $ai['sort_by'] : 'nom';
                $order   = ($ai['order']??'asc') === 'desc' ? 'desc' : 'asc';
                $sorted  = $clients;
                usort($sorted, function($a, $b) use ($sort_by, $order) {
                    $va = strtolower($a[$sort_by] ?? '');
                    $vb = strtolower($b[$sort_by] ?? '');
                    $cmp = strcmp($va, $vb);
                    return $order === 'desc' ? -$cmp : $cmp;
                });
                return ['success'=>true,'action'=>'list','reply'=>$reply,'clients'=>$sorted];

            case 'list':
            case 'search':
                $filter  = strtolower($ai['filter'] ?? '');
                $results = array_values(array_filter($clients, function($c) use ($filter) {
                    if (empty($filter)) return true;
                    return str_contains(strtolower($c['nom'].' '.$c['prenom'].' '.$c['email'].' '.($c['telephone']??'')), $filter);
                }));
                return ['success'=>true,'action'=>'list','reply'=>$reply,'clients'=>$results];

            case 'create':
                $d = $ai['data'] ?? [];
                if (empty($d['email']) || empty($d['nom']) || empty($d['prenom'])) {
                    return ['success'=>false,'action'=>'error','reply'=>'⚠️ Informations manquantes : nom, prénom et email sont requis.'];
                }
                $ex = $this->db->prepare("SELECT id FROM user WHERE email=:e");
                $ex->execute([':e'=>$d['email']]);
                if ($ex->fetch()) return ['success'=>false,'action'=>'error','reply'=>'⚠️ Un client avec cet email existe déjà.'];
                $mdp  = $d['mot_de_passe'] ?? 'TempPass123!';
                $stmt = $this->db->prepare("INSERT INTO user (nom,prenom,email,telephone,adresse,mot_de_passe,statut,post,email_verified) VALUES (:n,:p,:e,:t,:a,:m,:s,'client',1)");
                $ok   = $stmt->execute([':n'=>$d['nom'],':p'=>$d['prenom'],':e'=>$d['email'],':t'=>$d['telephone']??null,':a'=>$d['adresse']??null,':m'=>password_hash($mdp,PASSWORD_DEFAULT),':s'=>$d['statut']??'actif']);
                $newId = $this->db->lastInsertId();
                return ['success'=>$ok,'action'=>'create','reply'=>$ok?"✅ Client **{$d['prenom']} {$d['nom']}** créé (ID: $newId). Mot de passe : `$mdp`":'❌ Erreur création.','clients'=>$this->getAllClients()];

            case 'update':
                $id = (int)($ai['id']??0); $d = $ai['data']??[];
                if (!$id) return ['success'=>false,'action'=>'error','reply'=>'⚠️ ID manquant pour la modification.'];
                $stmt = $this->db->prepare("UPDATE user SET nom=:n,prenom=:p,email=:e,telephone=:t,adresse=:a,statut=:s WHERE id=:id AND post='client'");
                $ok = $stmt->execute([':n'=>$d['nom']??'',':p'=>$d['prenom']??'',':e'=>$d['email']??'',':t'=>$d['telephone']??null,':a'=>$d['adresse']??null,':s'=>$d['statut']??'actif',':id'=>$id]);
                return ['success'=>$ok,'action'=>'update','reply'=>$ok?"✅ Client ID $id modifié avec succès.":'❌ Erreur modification.','clients'=>$this->getAllClients()];

            case 'delete':
                $id = (int)($ai['id']??0);
                if (!$id) return ['success'=>false,'action'=>'error','reply'=>'⚠️ ID manquant pour la suppression.'];
                $r = $this->db->prepare("SELECT nom,prenom FROM user WHERE id=:id AND post='client'"); $r->execute([':id'=>$id]);
                $client = $r->fetch();
                if (!$client) return ['success'=>false,'action'=>'error','reply'=>"⚠️ Aucun client trouvé avec l'ID $id."];
                $stmt = $this->db->prepare("DELETE FROM user WHERE id=:id AND post='client'");
                $ok = $stmt->execute([':id'=>$id]);
                return ['success'=>$ok,'action'=>'delete','reply'=>$ok?"✅ Client **{$client['prenom']} {$client['nom']}** supprimé.":'❌ Erreur suppression.','clients'=>$this->getAllClients()];

            case 'block':
                $id = (int)($ai['id']??0);
                $statut = ($ai['statut']??'bloque')==='actif'?'actif':'bloque';
                if (!$id) return ['success'=>false,'action'=>'error','reply'=>'⚠️ ID manquant.'];
                $stmt = $this->db->prepare("UPDATE user SET statut=:s WHERE id=:id AND post='client'");
                $ok = $stmt->execute([':s'=>$statut,':id'=>$id]);
                $label = $statut==='bloque'?'bloqué':'débloqué';
                return ['success'=>$ok,'action'=>'block','reply'=>$ok?"✅ Client ID $id $label.":'❌ Erreur.','clients'=>$this->getAllClients()];

            default:
                return ['success'=>true,'action'=>'unknown','reply'=>$reply?:"Je n'ai pas compris. Exemples :\n• 'Liste tous les clients'\n• 'Ajoute un client : Ali Ben Salah, email ali@mail.com, tél 0612345678'\n• 'Supprime le client ID 3'\n• 'Modifie le client 5 : nouveau téléphone 0699887766'\n• 'Bloque le client ID 2'"];
        }
    }
}

$controller = new AIController();
$action = $_GET['action'] ?? 'showAssistant';
if (in_array($action, ['showAssistant','chat'])) {
    $controller->$action();
} else {
    $controller->showAssistant();
}
