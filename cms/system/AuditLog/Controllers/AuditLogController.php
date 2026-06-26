<?php

namespace Modules\AuditLog\Controllers;

use Modules\Kernel\Controllers\AdminController;

class AuditLogController extends AdminController
{
    public function audit()
    {
        $rows = db_connect()->table('admin_action_logs')->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();
        return $this->render('Modules\AuditLog\Views\audit_logs', ['pageTitle' => 'Audit log', 'rows' => $rows]);
    }

    public function suspicious()
    {
        $rows = db_connect()->table('suspicious_logs')->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();
        return $this->render('Modules\AuditLog\Views\suspicious_logs', ['pageTitle' => 'Suspicious logs', 'rows' => $rows]);
    }
}
