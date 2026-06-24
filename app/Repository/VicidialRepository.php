<?php
namespace App\Repository;

use PDO;
use PDOException;

class VicidialRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listExists(string $listId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM vicidial_lists WHERE list_id = :list_id"
        );
        $stmt->execute([':list_id' => $listId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function createList(
        string $listId,
        string $listName,
        string $campaignId,
        string $listDescription,
        string $dateDateAndTime
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO vicidial_lists (
                list_id, list_name, campaign_id, active, list_description, list_changedate,
                list_lastcalldate, reset_time, agent_script_override, campaign_cid_override,
                am_message_exten_override, drop_inbound_group_override, xferconf_a_number,
                xferconf_b_number, xferconf_c_number, xferconf_d_number, xferconf_e_number,
                web_form_address, web_form_address_two, time_zone_setting, inventory_report,
                expiration_date, na_call_url, local_call_time, web_form_address_three, status_group_id
             ) VALUES (
                :list_id, :list_name, :campaign_id, :active, :list_description, :list_changedate,
                NULL, :reset_time, :agent_script_override, :campaign_cid_override,
                :am_message_exten_override, :drop_inbound_group_override, :xferconf_a_number,
                :xferconf_b_number, :xferconf_c_number, :xferconf_d_number, :xferconf_e_number,
                NULL, NULL, :time_zone_setting, :inventory_report,
                :expiration_date, NULL, :local_call_time, NULL, :status_group_id
             )'
        );
        $stmt->execute([
            ':list_id'                     => $listId,
            ':list_name'                   => $listName,
            ':campaign_id'                 => $campaignId,
            ':active'                      => 'Y',
            ':list_description'            => $listDescription,
            ':list_changedate'             => $dateDateAndTime,
            ':reset_time'                  => '',
            ':agent_script_override'       => '',
            ':campaign_cid_override'       => '',
            ':am_message_exten_override'   => '',
            ':drop_inbound_group_override' => '',
            ':xferconf_a_number'           => '',
            ':xferconf_b_number'           => '',
            ':xferconf_c_number'           => '',
            ':xferconf_d_number'           => '',
            ':xferconf_e_number'           => '',
            ':time_zone_setting'           => 'COUNTRY_AND_AREA_CODE',
            ':inventory_report'            => 'Y',
            ':expiration_date'             => '2099-12-31',
            ':local_call_time'             => 'campaign',
            ':status_group_id'             => ''
        ]);
    }

    public function phoneExists(string $listId, string $phoneNumber): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM vicidial_list WHERE list_id = :list_id AND phone_number = :phone_number"
        );
        $stmt->execute([':list_id' => $listId, ':phone_number' => $phoneNumber]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function insertPhone(
        string $dateDateAndTime,
        string $listId,
        string $phoneNumber,
        string $customerName,
        string $address,
        string $postalCode,
        string $city
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO vicidial_list (
                entry_date, modify_date, status, user, vendor_lead_code, source_id, list_id, gmt_offset_now,
                called_since_last_reset, phone_code, phone_number, title, first_name, middle_initial, last_name,
                address1, address2, address3, city, state, province, postal_code, country_code, gender,
                date_of_birth, alt_phone, email, security_phrase, comments, called_count, last_local_call_time,
                rank, owner, entry_list_id
            ) VALUES (
                :entry_date, :modify_date, :status, :user, :vendor_lead_code, :source_id, :list_id, :gmt_offset_now,
                :called_since_last_reset, :phone_code, :phone_number, :title, :first_name, :middle_initial, :last_name,
                :address1, :address2, :address3, :city, :state, :province, :postal_code, :country_code, :gender,
                :date_of_birth, :alt_phone, :email, :security_phrase, :comments, :called_count, :last_local_call_time,
                :rank, :owner, :entry_list_id
            )'
        );
        $stmt->execute([
            ':entry_date'              => $dateDateAndTime,
            ':modify_date'             => '0000-00-00 00:00:00',
            ':status'                  => 'NEW',
            ':user'                    => '',
            ':vendor_lead_code'        => '',
            ':source_id'               => '',
            ':list_id'                 => $listId,
            ':gmt_offset_now'          => '-5.00',
            ':called_since_last_reset' => 'N',
            ':phone_code'              => '33',
            ':phone_number'            => $phoneNumber,
            ':title'                   => '',
            ':first_name'              => $customerName,
            ':middle_initial'          => '',
            ':last_name'               => '',
            ':address1'                => $address,
            ':address2'                => '',
            ':address3'                => '',
            ':city'                    => $city,
            ':state'                   => '',
            ':province'                => '',
            ':postal_code'             => $postalCode,
            ':country_code'            => '',
            ':gender'                  => 'U',
            ':date_of_birth'           => '0000-00-00',
            ':alt_phone'               => '',
            ':email'                   => '',
            ':security_phrase'         => '',
            ':comments'                => '',
            ':called_count'            => 0,
            ':last_local_call_time'    => '2008-01-01 00:00:00',
            ':rank'                    => '0',
            ':owner'                   => '',
            ':entry_list_id'           => 0
        ]);
    }

    public function beginTransaction(): void   { $this->pdo->beginTransaction(); }
    public function commit(): void             { $this->pdo->commit(); }
    public function rollBack(): void           { $this->pdo->rollBack(); }
}