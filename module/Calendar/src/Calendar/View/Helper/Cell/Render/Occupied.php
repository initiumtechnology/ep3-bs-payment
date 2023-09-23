<?php

namespace Calendar\View\Helper\Cell\Render;

use Square\Entity\Square;
use Zend\View\Helper\AbstractHelper;

class Occupied extends AbstractHelper
{

    public function __invoke($user, $userBooking, array $reservations, array $cellLinkParams, Square $square)
    {
        $view = $this->getView();

        //syslog(LOG_EMERG, print_r('Occupied', true));


        if ($user && $user->can('calendar.see-data')) {
            return $view->calendarCellRenderOccupiedForPrivileged($reservations, $cellLinkParams);
        } else if ($user) {
            if ($userBooking) {

                $cellLabel = $view->t('Your Booking');
                $cellGroup = ' cc-group-' . $userBooking->need('bid');
                $style = 'cc-own';

                 if ($userBooking->getMeta('directpay') == 'true' and $userBooking->get('status_billing')!= 'paid') {
                     $cellLabel = $view->t('Your Booking TRY');
                     $style = 'cc-try';
                 }

                return $view->calendarCellLink($cellLabel, $view->url('square', [], $cellLinkParams), $style . $cellGroup);
            } else {
                return $view->calendarCellRenderOccupiedForVisitors($reservations, $cellLinkParams, $square, $user);
            }
        } else {
            return $view->calendarCellRenderOccupiedForVisitors($reservations, $cellLinkParams, $square);
        }
    }

}
