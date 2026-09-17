<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNoPdToPerjalananDinas extends Migration
{
    public function up()
    {
        $fields = $this->db->getFieldNames('perjalanan_dinas');
        if (!in_array('no_pd', $fields, true)) {
            $this->forge->addColumn('perjalanan_dinas', [
                'no_pd' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'id',
                ],
            ]);
        }

        $idxPerjadin = array_column($this->db->query("SHOW INDEX FROM perjalanan_dinas")->getResultArray(), 'Key_name');
        if (!in_array('idx_perjadin_no_pd', $idxPerjadin, true)) {
            $this->forge->addKey('no_pd', false, false, 'idx_perjadin_no_pd');
            $this->forge->processIndexes('perjalanan_dinas');
        }
    }

    public function down()
    {
        $idxPerjadin = array_column($this->db->query("SHOW INDEX FROM perjalanan_dinas")->getResultArray(), 'Key_name');
        if (in_array('idx_perjadin_no_pd', $idxPerjadin, true)) {
            $this->forge->dropKey('perjalanan_dinas', 'idx_perjadin_no_pd');
        }

        $fields = $this->db->getFieldNames('perjalanan_dinas');
        if (in_array('no_pd', $fields, true)) {
            $this->forge->dropColumn('perjalanan_dinas', 'no_pd');
        }
    }
}
