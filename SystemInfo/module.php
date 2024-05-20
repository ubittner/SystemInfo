<?php

/**
 * @project       SystemInfo/SystemInfo/
 * @file          module.php
 * @author        Ulrich Bittner
 * @copyright     2024 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnreachableStatementInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

class SystemInfo extends IPSModule
{
    private const MODULE_PREFIX = 'SI';

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        $id = @$this->GetIDForIdent('Platform');
        $this->RegisterVariableString('Platform', 'Plattform', '', 10);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('Platform'), 'Notebook');
        }

        $id = @$this->GetIDForIdent('Symcon');
        $this->RegisterVariableString('Symcon', 'IP-Symcon', '', 20);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('Symcon'), 'IPS');
        }

        $id = @$this->GetIDForIdent('PHP');
        $this->RegisterVariableString('PHP', 'PHP', '', 30);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('PHP'), 'Database');
        }

        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.UpdateInfo';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Aktualisieren', 'Repeat', -1);
        $this->RegisterVariableInteger('UpdateInfo', 'Aktualisierung', $profile, 40);
        $this->EnableAction('UpdateInfo');
    }

    public function ApplyChanges(): void
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        $this->UpdateInfo();
    }

    public function Destroy(): void
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['UpdateInfo'];
        foreach ($profiles as $profile) {
            $profileName = self::MODULE_PREFIX . '.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data): void
    {
        $this->SendDebug('MessageSink', 'Message from SenderID ' . $SenderID . ' with Message ' . $Message . "\r\n Data: " . print_r($Data, true), 0);
        if ($Message == IPS_KERNELSTARTED) {
            $this->KernelReady();
        }
    }

    #################### Request action

    public function RequestAction($Ident, $Value): void
    {
        if ($Ident == 'UpdateInfo') {
            $this->UpdateInfo();
        }
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function UpdateInfo(): void
    {
        $this->SetValue('Platform', IPS_GetKernelPlatform() . ', ' . IPS_GetKernelArchitecture());
        $this->SetValue('Symcon', IPS_GetKernelVersion() . ', ' . IPS_GetLiveUpdateVersion());
        $this->SetValue('PHP', phpversion());
    }
}