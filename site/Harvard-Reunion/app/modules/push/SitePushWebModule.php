<?php

/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

includePackage('Push');

class SitePushWebModule extends PushWebModule
{

    private $messageYear;
    private $messageText;
    private $didSend = false;

    public function initialize() {
        $this->requiresAdmin();
    }

    public function initializeForPage() {
        $this->messageYear = $this->getArg('year');
        $this->messageText = $this->getArg('message');

        switch ($this->page) {
            case 'index':
                if ($this->messageYear) {
                    $this->assign('selectedYear', $this->messageYear);
                }

                if ($this->messageYear && $this->messageText) {
                    $this->assign('didSend', true);
                    if ($this->messageYear == 'All') {
                        $recipient = 'All classes';
                    } else {
                        $recipient = 'the Class of '.$this->messageYear;
                    }

                    $this->assign('messageRecipient', $recipient);
                    $this->assign('messageText', $this->messageText);
                }

                $years = array();
                foreach (Schedule::getAllReunionYears() as $yearInfo) {
                    $years[] = $yearInfo['year'];
                }
                array_unshift($years, 'All');
                $this->assign('years', $years);
                break;
            case 'sendMessage':
                if ($this->messageYear == 'All') {
                    $subscribers = PushDB::getActiveDevices('ios');
                } else {
                    $subscribers = PushDB::getSubscribersForTag('ios', $this->messageYear);
                }
                foreach ($subscribers as $subscriber) {
                    $device = PushClientDevice::factory(
                        $subscriber['device_id'],
                        'ios',
                        PushClientDevice::SOURCE_DAEMON);
                    $device->createNotification(
                        $this->messageYear,
                        $this->messageText,
                        0);
                }

                $this->redirectTo('index', $this->args);
                break;
        }
    }




}

