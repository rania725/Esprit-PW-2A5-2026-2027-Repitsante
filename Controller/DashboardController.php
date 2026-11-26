<?php
declare(strict_types=1);

final class DashboardController
{
    public function show(UserModel $account, string $query): void
    {
        $metrics = [
            ['calendar', '24', 'Rendez-vous du jour'],
            ['layers', '8', 'Parcours à organiser'],
            ['refresh', '3', 'Changements à traiter'],
        ];
        $pathways = [
            ['P-024', 'Analyse + consultation', 'À valider', 'check', 'teal', 'Analyse le lundi à 09:00. Consultation le mercredi à 09:00.'],
            ['P-031', 'Radiologie + consultation', 'Horaire manquant', 'clock', 'amber', 'Radiologie le lundi à 14:00. Consultation le mercredi à 15:00. Le planning indique aussi une analyse le vendredi à 09:30.'],
            ['P-042', 'Analyse + radiologie', 'Confirmé', 'check', 'green', 'Radiologie le jeudi à 11:00. Le planning indique aussi deux créneaux de consultation.'],
        ];
        $alerts = [
            ['calendar', 'Un horaire est disponible', 'Un créneau vient de se libérer en Radiologie.', 'teal'],
            ['refresh', 'Un rendez-vous a été reporté', 'Le rendez-vous du patient P-031 a été reporté.', 'amber'],
        ];
        $week = [
            ['Lundi', '20 janv.', [['09:00', 'P-024 – Analyse'], ['14:00', 'P-031 – Radiologie']]],
            ['Mardi', '21 janv.', [['10:30', 'P-042 – Consultation']]],
            ['Mercredi', '22 janv.', [['09:00', 'P-024 – Consultation'], ['15:00', 'P-031 – Consultation']]],
            ['Jeudi', '23 janv.', [['11:00', 'P-042 – Radiologie']]],
            ['Vendredi', '24 janv.', [['09:30', 'P-031 – Analyse'], ['14:30', 'P-042 – Consultation']]],
        ];

        if ($query !== '') {
            $pattern = '~' . preg_quote($query, '~') . '~iu';
            $matches = static fn (string $text): bool => preg_match($pattern, $text) === 1;
            $pathways = array_filter($pathways, static fn (array $row): bool => $matches(implode(' ', $row)));
            foreach ($week as &$day) {
                $day[2] = array_filter($day[2], static fn (array $slot): bool => $matches(implode(' ', $slot)));
            }
            unset($day);
        }
        $section = 'dashboard';
        render('admin/dashboard', 'Tableau de bord', compact('account', 'metrics', 'pathways', 'alerts', 'week', 'query', 'section'), 'admin');
    }
}
