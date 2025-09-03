<?php

/*  Poweradmin, a friendly web-based admin tool for PowerDNS.
 *  See <https://www.poweradmin.org> for more details.
 *
 *  Copyright 2007-2010 Rejo Zenger <rejo@zenger.nl>
 *  Copyright 2010-2025 Poweradmin Development Team
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Script that handles deletion of supermasters
 *
 * @package     Poweradmin
 * @copyright   2007-2010 Rejo Zenger <rejo@zenger.nl>
 * @copyright   2010-2025 Poweradmin Development Team
 * @license     https://opensource.org/licenses/GPL-3.0 GPL
 */

namespace Poweradmin\Application\Controller;

use Poweradmin\BaseController;
use Poweradmin\Domain\Service\DnsRecord;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteSupermasterController extends BaseController
{
    public function __construct(array $request)
    {
        parent::__construct($request);
    }

    public function run(): void
    {
        $this->checkPermission('supermaster_edit', _("You do not have the permission to delete a supermaster."));

        if (isset($_GET['confirm'])) {
            $this->deleteSuperMaster();
        } else {
            $this->showDeleteSuperMaster();
        }
    }

    private function deleteSuperMaster(): void
    {
        $constraints = [
            'master_ip' => [
                new Assert\NotBlank(),
                new Assert\Ip()
            ],
            'ns_name' => [
                new Assert\NotBlank(),
                new Assert\Hostname()
            ]
        ];

        $this->setValidationConstraints($constraints);

        if (!$this->doValidateRequest($_GET)) {
            $this->showFirstValidationError($_GET);
            return;
        }

        $master_ip = filter_input(INPUT_GET, 'master_ip', FILTER_VALIDATE_IP);
        $ns_name = filter_input(INPUT_GET, 'ns_name', FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);

        if ($master_ip === false) {
            $this->setMessage('list_supermasters', 'error', _('Invalid IP address.'));
            $this->redirect('/supermasters');
        }

        if (empty($ns_name)) {
            $this->setMessage('list_supermasters', 'error', _('Invalid NS name.'));
            $this->redirect('/supermasters');
        }

        $dnsRecord = new DnsRecord($this->db, $this->getConfig());
        if (!$dnsRecord->supermasterIpNameExists($master_ip, $ns_name)) {
            $this->setMessage('list_supermasters', 'error', _('Super master does not exist.'));
            $this->redirect('/supermasters');
        }
        if ($dnsRecord->deleteSupermaster($master_ip, $ns_name)) {
            $this->setMessage('list_supermasters', 'success', _('The supermaster has been deleted successfully.'));
            $this->redirect('/supermasters');
        }
    }

    private function showDeleteSuperMaster(): void
    {
        $master_ip = htmlspecialchars($_GET['master_ip']);
        $dnsRecord = new DnsRecord($this->db, $this->getConfig());
        $info = $dnsRecord->getSupermasterInfoFromIp($master_ip);

        $this->render('delete_supermaster.html', [
            'master_ip' => $master_ip,
            'info' => $info
        ]);
    }
}
