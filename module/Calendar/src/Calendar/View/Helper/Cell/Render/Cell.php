<?php

namespace Calendar\View\Helper\Cell\Render;

use Zend\View\Helper\AbstractHelper;
use Base\Manager\OptionManager;
use \Square\Factory\Cart;


class Cell extends AbstractHelper
{

    private function arrays_match($array1, $array2) {
        // Check if $array2 is an array and contains at least one item
        if (!is_array($array2) || empty($array2)) {
            return false; // $array2 should be a non-empty array.
        }

        foreach ($array2 as $item) {
            if (count($array1) !== count($item)) {
                continue; // Skip items with different lengths.
            }

            $match = true; // Assume a match initially.

            foreach ($array1 as $key => $value) {
                if (!isset($item[$key]) || $value !== $item[$key]) {
                    $match = false; // If any key or value doesn't match, set $match to false.
                    break; // No need to continue checking this item.
                }
            }

            if ($match) {
                return true; // If a match is found, return true.
            }
        }

        return false; // No match found in $array2.
    }


    public function __construct(OptionManager $optionManager)
    {
        $this->optionManager = $optionManager;
    }

    public function __invoke($walkingDate, $walkingTime, $timeBlock, $square, $user, $reservationsForCell, $eventsForCell)
    {
        $view = $this->getView();

        $cellLinkParams = ['query' => [
            'ds' => $walkingDate->format('Y-m-d'),
            'ts' => gmdate('H:i', $walkingTime),
            'te' => gmdate('H:i', $walkingTime + $timeBlock),
            's' => $square->need('sid'),
        ]];


        if ($cellLinkParams['query']['te'] == '00:00') {
            $cellLinkParams['query']['te'] = '24:00';
        }

        /* Check events */

        if ($eventsForCell) {
            if (count($eventsForCell) > 1 || count($reservationsForCell) > 0) {
                return $view->calendarCellRenderEventConflict($user, $eventsForCell, $reservationsForCell, $cellLinkParams);
            } else {
                $event = current($eventsForCell);
                return $view->calendarCellRenderEvent($user, $event, $cellLinkParams);
            }
        }

        /* Check bookings */

        $capacity = $square->need('capacity');
        $capacityHeterogenic = $square->need('capacity_heterogenic');

        $quantity = 0;

        $userBooking = null;

        foreach ($reservationsForCell as $reservation) {
            $booking = $reservation->needExtra('booking');
            $quantity += $booking->need('quantity');

            if ($user && $user->need('uid') == $booking->need('uid')) {
                $userBooking = $booking;
            }
        }

        if ($capacity > $quantity) {
            if ($quantity && ! $capacityHeterogenic) {
                $cellFree = false;
            } else {
                $cellFree = true;
            }
        } else {
            $cellFree = false;
        }

        if ($capacity - $quantity < 0) {
            if ($user && $user->can('calendar.see-data')) {
                return $view->calendarCellLink($this->view->t('Conflict'), $view->url('backend/booking/edit', [], $cellLinkParams), 'cc-conflict');
            }
        }

        /* Check for club reserved time blocks in club-exception days*/
        $cellReserved = false;

        $clubExceptions = $this->optionManager->get('service.calendar.club-exceptions');
        $displayClubExceptions = $this->optionManager->get('service.calendar.display-club-exceptions');

        if ($clubExceptions) {
            $clubExceptions = preg_split('~(\\n|,)~', $clubExceptions);
            $clubExceptionsExceptions = [];

            $clubExceptionsCleaned = [];

            foreach ($clubExceptions as $clubException) {
                $clubException = trim($clubException);

                if ($clubException) {
                    if ($clubException[0] === '+') {
                        $clubExceptionsExceptions[] = trim($clubException, '+');
                    } else {
                        $clubExceptionsCleaned[] = $clubException;
                    }
                }
            }

            $clubExceptions = $clubExceptionsCleaned;

            // syslog(LOG_EMERG, '|'.$timeBlock.'|');
            // syslog(LOG_EMERG, '|'.$walkingDate->format('Y-m-d').'|');

            if (in_array($walkingDate->format('Y-m-d'), $clubExceptions) ||
                in_array($walkingDate->format('l'), $clubExceptions)) {

                // syslog(LOG_EMERG, '|'.$walkingDate->format($view->t('Y-m-d')).'|');

                if (!in_array($walkingDate->format('Y-m-d'), $clubExceptionsExceptions)) {
                    // clone is important to  not modify the origin walkingDate
                    $resTimeStart = clone $walkingDate;
                    $resTimeEnd = clone $walkingDate;
                    $timeStart = clone $walkingDate;
                    $timeEnd = clone $walkingDate;
                    $timeBlockMinutes = $timeBlock/60;
                    $timeEnd = $timeEnd->modify("+{$timeBlockMinutes} minutes");

                    $resTimeStartParam = $square->getMeta('club_reserved_time_start');
                    $resTimeEndParam = $square->getMeta('club_reserved_time_end');
                    $resTimeStartParts = explode(':', $resTimeStartParam);
                    $resTimeEndParts = explode(':', $resTimeEndParam);

                    $resTimeStart->setTime($resTimeStartParts[0], $resTimeStartParts[1]);
                    $resTimeEnd->setTime($resTimeEndParts[0], $resTimeEndParts[1]);

                    if ( (($timeStart >= $resTimeStart) && ($timeStart < $resTimeEnd))
                          || (($timeEnd > $resTimeStart) && ($timeEnd <= $resTimeEnd)) ) {
                              $cellReserved = true;
                              // syslog(LOG_EMERG, '|'.$cellReserved.'|');
                   }
                }
            }
        }

        $cartService = Cart::getInstance();
        $cartItems = $cartService->getItems();

        $cellLinkParamsCart = array();
        if (!empty($cartItems[0]['dateStart'])) {
            foreach ($cartItems as $item) {
                $cellLinkParamsCart[] = array(
                    'query' => array(
                        'ds' => $item['dateStart'],
                        'ts' => $item['timeStart'],
                        'te' => $item['timeEnd'],
                        's' => $item['square']
                    )
                    );
            }
        }
        else {
            $cellLinkParamsCart = [];
        }


        if ($this->arrays_match($cellLinkParams, $cellLinkParamsCart)) {
            $match = true;
        } else {
            $match = false;
        }

            //syslog(LOG_EMERG, print_r($cellLinkParams, true));
            //syslog(LOG_EMERG, print_r($cellLinkParamsCart11, true));
            //syslog(LOG_EMERG, print_r('rega', true));

            //syslog(LOG_EMERG, print_r($cellLinkParamsCart, true));

            if ($cellFree && $match == false) {
                //syslog(LOG_EMERG, print_r('Free cell', true));
                if ($cellReserved && $displayClubExceptions && ($user && !$user->getMeta('member'))) {
                    return $view->calendarCellRenderReserved($user, $userBooking, $reservationsForCell, $cellLinkParams, $square);   
                } else {
                    return $view->calendarCellRenderFree($user, $userBooking, $reservationsForCell, $cellLinkParams, $square);
                }
            } else if ($match == false) {
                return $view->calendarCellRenderOccupied($user, $userBooking, $reservationsForCell, $cellLinkParams, $square);
            }
            else if ($match == true) {
                //syslog(LOG_EMERG, print_r('Cart cell', true));

                if (empty($cartItems[0]['dateStart'])) {
                    //syslog(LOG_EMERG, print_r('empty array', true));
                } else {
                        //syslog(LOG_EMERG, print_r($cellLinkParamsCart, true));
                        return $view->CalendarCellRenderCart($user, $cellLinkParamsCart);
                    }

            }

        }

    }
