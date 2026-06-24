<?php
namespace App\Repository;

use PDO;
use PDOException;

class BashOrdreRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getLastPending(): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id AS idOrdre, database_name AS selectDataBase, table_name AS nameTable,
                        phone_code, number_use, id_lot, extra, id_user_connecte, type_add
                 FROM tb_bash_ordre
                 WHERE status = 0
                 ORDER BY id DESC
                 LIMIT 1"
            );
            $stmt->execute();
            $ordre = $stmt->fetch(PDO::FETCH_ASSOC);
            return $ordre ?: null;
        } catch (PDOException $e) {
            error_log("Fetch last pending bash ordre failed: " . $e->getMessage());
            return null;
        }
    }

    public function updateStatus(int $idOrdre, int $status = 1): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE tb_bash_ordre 
                 SET status = :status, updated_at = CURRENT_TIMESTAMP 
                 WHERE id = :idOrdre"
            );
            return $stmt->execute([':status' => $status, ':idOrdre' => $idOrdre]);
        } catch (PDOException $e) {
            error_log("Failed to update bash ordre status: " . $e->getMessage());
            return false;
        }
    }

    public function updateBatchManagement(int $idLot, int $newNumberUses, string $dateTime): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE tb_batch_management 
             SET number_uses = :number_uses, date_last_use = :date_last_use 
             WHERE id = :id"
        );
        return $stmt->execute([
            ':number_uses'  => $newNumberUses,
            ':date_last_use' => $dateTime,
            ':id'           => $idLot
        ]);
    }

    public function updateLastIdCrm(int $idLot, string $listId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE tb_batch_management 
             SET last_id_crm = :last_id_crm 
             WHERE id = :id"
        );
        $stmt->execute([':last_id_crm' => $listId, ':id' => $idLot]);
        return $stmt->rowCount() > 0;
    }

    public function insertHistory(int $idLot, int $idUser, string $listId): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tb_bash_history
                (id_bash, id_user, id_crm, number_added_CRM, number_promises, number_sda, number_ok, number_answer)
             VALUES
                (:id_bash, :id_user, :id_crm, 0, 0, 0, 0, 0)"
        );
        $stmt->execute([
            ':id_bash' => $idLot,
            ':id_user' => $idUser,
            ':id_crm'  => $listId
        ]);
    }
}