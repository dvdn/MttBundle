<?php

namespace CanalTP\MttBundle\Tests\Functional\Controller;

class CalendarControllerTest extends AbstractControllerTest
{
    const EXTERNAL_NETWORK_ID = 'network:JDR:2';

    private function getViewRoute()
    {
        return $this->generateRoute(
            'canal_tp_mtt_calendar_view',
            // fake params since we mock navitia
            array(
                'externalNetworkId' => self::EXTERNAL_NETWORK_ID,
                'externalRouteId' => 'test',
                'externalStopPointId' => 'test'
            )
        );
    }

    public function setUp($login = true)
    {
        parent::setUp($login);
        $this->setService('canal_tp_mtt.navitia', $this->getMockedNavitia());
    }

    /**
     * Tests empty list of calendar
     */
    public function testCalendarsEmptyListAction()
    {
        $route = $this->generateRoute('canal_tp_mtt_calendars_list');
        $crawler = $this->doRequestRoute($route);

        // Test title
        $this->assertEquals('Liste des calendriers', $crawler->filter('h1')->text(), 'Wrong title.');

        // Test link and text of create button
        $createButton = $crawler->selectLink('Créer un calendrier');
        $this->assertCount(1, $createButton, 'Wrong text for create button.');
        $this->assertContains('/mtt/calendars/create', $createButton->link()->getUri());

        // Test link and text of export button
        $exportButton = $crawler->selectLink('Exporter');
        $this->assertCount(1, $exportButton, 'Wrong text for export button.');
        $this->assertContains('/mtt/calendars/export', $exportButton->link()->getUri());

        // Test export button is disabled
        $this->assertEquals('disabled', $exportButton->attr('disabled'), 'Export button should be disabled.');

        // Test if there is no calendars
        $this->assertCount(
            1,
            $crawler->filter('html:contains("Aucun calendrier")'),
            'There should be 0 calendars.'
        );
    }

    /**
     * Tests that calendar creation.
     */
    public function testCalendarsCreateAction()
    {
        $route = $this->generateRoute('canal_tp_mtt_calendars_create');

        $crawler = $this->doRequestRoute($route);

        $this->assertFormFields($crawler);

        $form = $crawler->selectButton('Valider')->form();
        $this->assertFormErrors($form);
        $form['mtt_calendar[title]'] = 'Samedi et dimanche';
        $form['mtt_calendar[startDate]'] = '01/01/2016';
        $form['mtt_calendar[endDate]'] = '01/06/2016';
        $form['mtt_calendar[weeklyPattern]'][5]->tick();
        $form['mtt_calendar[weeklyPattern]'][6]->tick();

        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('html:contains("Le calendrier a été créé")'));
        // Assert that button exist in create calendar page
        $backButtonCrawler = $crawler->filter('a#back_button');
        $this->assertCount(1, $backButtonCrawler, 'Retour à la liste des calendriers');
        $this->assertEquals('/mtt/calendars/list', $backButtonCrawler->attr('href'));
        // Assert that back button redirect to calendar list page
        $calendarsBackButtonLink = $backButtonCrawler->link();
        $crawler = $this->client->click($calendarsBackButtonLink);
        $this->assertEquals($calendarsBackButtonLink->getUri(), $this->client->getRequest()->getUri());
    }

    public function testCalendarsEditAction()
    {
        $route = $this->generateRoute('canal_tp_mtt_calendars_list');

        $crawler = $this->doRequestRoute($route);

        // Assert that there is a calendar in the calendars list
        $calendarsFirstRow = $crawler->filter('#main-container table tbody tr')->first();
        $this->assertCount(1, $calendarsFirstRow);

        // Assert that edit button exists for a calendar in the calendars list
        $calendarsFirstRowEditButton = $calendarsFirstRow->filter('td a[href*="edit"]');
        $this->assertCount(1, $calendarsFirstRowEditButton);

        // Assert that edit button redirect to calendar edit page
        $this->assertEquals('/mtt/calendars/edit/1', $calendarsFirstRowEditButton->attr('href'));

        $calendarsFirstRowEditLink = $calendarsFirstRowEditButton->link();
        $crawler = $this->client->click($calendarsFirstRowEditLink);
        $this->assertEquals($calendarsFirstRowEditLink->getUri(), $this->client->getRequest()->getUri());

        $this->assertFormFields($crawler);

        $formBackButtonCrawler = $crawler->filter('a#back_button');
        $this->assertCount(1, $formBackButtonCrawler, 'Retour à la liste des calendriers');

        // Assert cancel editing calendar
        $this->assertEquals('/mtt/calendars/list', $formBackButtonCrawler->attr('href'));

        $formCrawler = $crawler->selectButton('Valider')->form();
        $this->assertFormErrors($formCrawler);

        // Assert calendar is updated
        $formCrawler['mtt_calendar[title]'] = 'Mardi et Samedi';
        $formCrawler['mtt_calendar[startDate]'] = '01/02/2016';
        $formCrawler['mtt_calendar[endDate]'] = '01/07/2016';
        $formCrawler['mtt_calendar[weeklyPattern]'][1]->tick();
        $formCrawler['mtt_calendar[weeklyPattern]'][5]->tick();
        $crawler = $this->client->submit($formCrawler);

        $crawler = $this->client->followRedirect();
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('html:contains("Le calendrier a été modifié")'));

        $calendarsFirstRowData = $crawler->filter('#main-container table tbody tr td');
        $this->assertEquals('Mardi et Samedi', $calendarsFirstRowData->first()->text());

        // Assert editing calendar with bad id not found
        $route = $this->generateRoute('canal_tp_mtt_calendars_edit', array('calendarId' => 1000));
        $crawler = $this->doRequestRoute($route, 404);
    }

    /**
     * Assert form fields are presents
     *
     * @param $crawler
     */
    private function assertFormFields($crawler)
    {
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(1, $crawler->filter('h3')->count(), 'Expected h3 title.');

        // Test fields exist
        $this->assertCount(1, $crawler->filter('html:contains("Titre")'));
        $this->assertCount(1, $crawler->filter('form input[name="mtt_calendar[title]"]'), 'Champ Titre');

        $this->assertCount(1, $crawler->filter('html:contains("Date de début")'));
        $this->assertCount(1, $crawler->filter('form input[name="mtt_calendar[startDate]"]'), 'Champ Date de début');

        $this->assertCount(1, $crawler->filter('html:contains("Date de fin")'));
        $this->assertCount(1, $crawler->filter('form input[name="mtt_calendar[endDate]"]'), 'Champ Date de fin');

        $this->assertCount(1, $crawler->filter('html:contains("Jours de validité")'));

        $this->assertCount(1, $crawler->filter('html:contains("Lundi")'));
        $this->assertCount(1, $crawler->filter('html:contains("Mardi")'));
        $this->assertCount(1, $crawler->filter('html:contains("Mercredi")'));
        $this->assertCount(1, $crawler->filter('html:contains("Jeudi")'));
        $this->assertCount(1, $crawler->filter('html:contains("Vendredi")'));
        $this->assertCount(1, $crawler->filter('html:contains("Samedi")'));
        $this->assertCount(1, $crawler->filter('html:contains("Dimanche")'));
        $this->assertCount(7, $crawler->filter('form input[type="checkbox"]'), '7 checkboxes');

        $this->assertCount(1, $crawler->filter('form button[type="submit"]'), 'Bouton Valider');
    }
    
    /**
     * Tests list of calendar
     */
    public function testCalendarsListAction()
    {
        $route = $this->generateRoute('canal_tp_mtt_calendars_list');
        $crawler = $this->doRequestRoute($route);

        // Test link and text of export button
        $exportButton = $crawler->selectLink('Exporter');
        $this->assertCount(1, $exportButton, 'Wrong text for export button.');
        $this->assertContains('/mtt/calendars/export', $exportButton->link()->getUri());

        // Test export button is disabled
        $this->assertNull($exportButton->attr('disabled'), 'Export button should not be disabled.');

        $this->assertCount(1, $crawler->filter('tbody > tr'), 'There should be 1 calendar.');
    }

    public function testCalendarsDeleteAction()
    {
        $route = $this->generateRoute('canal_tp_mtt_calendars_list');

        $crawler = $this->doRequestRoute($route);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Assert that delete button exists for a calendar in the calendars list
        $calendarsFirstRowDeleteButton = $crawler->filter('#main-container table tbody tr td a[href*="delete"]');
        $this->assertCount(1, $calendarsFirstRowDeleteButton);

        // Assert that delete button containts delete link
        $this->assertEquals('/mtt/calendars/delete/1', $calendarsFirstRowDeleteButton->attr('href'));

        // Assert deleting calendar with bad id not found
        $route = $this->generateRoute('canal_tp_mtt_calendars_delete', array('calendarId' => 1000));
        $crawler = $this->doRequestRoute($route, 404);

        // Assert deleting calendar
        $route = $this->generateRoute('canal_tp_mtt_calendars_delete', array('calendarId' => 1));
        $crawler = $this->doRequestRoute($route, 302);

        $crawler = $this->client->followRedirect();
        $this->assertCount(1, $crawler->filter('html:contains("Le calendrier a bien été supprimé")'));
        
        // Assert that there is not any calendar in the calendars list
        $calendarsFirstRow = $crawler->filter('#main-container table tbody tr td')->first();
        $this->assertEquals("Aucun calendrier", trim($calendarsFirstRow->text()));
    }

    /**
     * Tests error when creating a calendar.
     *
     * @param $form Form Crawler
     */
    private function assertFormErrors($form)
    {
        $crawler = $this->client->submit($form);

        // Test all fields required
        $form['mtt_calendar[title]'] = null;
        $form['mtt_calendar[weeklyPattern]'][0]->untick();
        $form['mtt_calendar[weeklyPattern]'][1]->untick();
        $form['mtt_calendar[weeklyPattern]'][2]->untick();
        $form['mtt_calendar[weeklyPattern]'][3]->untick();
        $form['mtt_calendar[weeklyPattern]'][4]->untick();
        $form['mtt_calendar[weeklyPattern]'][5]->untick();
        $form['mtt_calendar[weeklyPattern]'][6]->untick();
        $form['mtt_calendar[startDate]'] = null;
        $form['mtt_calendar[endDate]'] = null;
        $crawler = $this->client->submit($form);

        $this->assertCount(4, $crawler->filter('.has-error'));

        // Test start date < end date
        $form['mtt_calendar[title]'] = 'Samedi et dimanche';
        $form['mtt_calendar[weeklyPattern]'][5]->tick();
        $form['mtt_calendar[weeklyPattern]'][6]->tick();
        $form['mtt_calendar[startDate]'] = '02/01/2016';
        $form['mtt_calendar[endDate]'] = '01/01/2016';
        $crawler = $this->client->submit($form);

        $this->assertCount(1, $crawler->filter('.has-error'));

        // Test end date - start date < 1 day
        $form['mtt_calendar[title]'] = 'Samedi et dimanche';
        $form['mtt_calendar[weeklyPattern]'][5]->tick();
        $form['mtt_calendar[weeklyPattern]'][6]->tick();
        $form['mtt_calendar[startDate]'] = '01/01/2016';
        $form['mtt_calendar[endDate]'] = '01/01/2016';
        $crawler = $this->client->submit($form);

        $this->assertCount(1, $crawler->filter('.has-error'));

        // Test no days selected
        $form['mtt_calendar[title]'] = 'Samedi et dimanche';
        $form['mtt_calendar[startDate]'] = '01/01/2016';
        $form['mtt_calendar[endDate]'] = '02/01/2016';
        $form['mtt_calendar[weeklyPattern]'][5]->untick();
        $form['mtt_calendar[weeklyPattern]'][6]->untick();
        $crawler = $this->client->submit($form);

        $this->assertCount(1, $crawler->filter('.has-error'));
    }

    public function testCalendarsPresentViewAction()
    {
        $crawler = $this->doRequestRoute($this->getViewRoute());

        $this->assertTrue($crawler->filter('h3')->count() == 1, 'Expected h3 title.');
        $this->assertTrue($crawler->filter('.nav.nav-tabs > li')->count() == 4, 'Expected 4 calendars. Found ' . $crawler->filter('.nav.nav-tabs > li')->count());
    }

    public function testCalendarsNamesViewAction()
    {
        $crawler = $this->doRequestRoute($this->getViewRoute());
        // comes from the stub
        $calendarsName = array('Semaine scolaire', 'Semaine hors scolaire', "Samedi", "Dimanche et fêtes");
        foreach ($calendarsName as $name) {
            $this->assertTrue(
                $crawler->filter('html:contains("' . $name . '")')->count() == 1,
                "Calendar $name not found in answer"
            );
        }
    }

    public function testHoursConsistencyViewAction()
    {
        $crawler = $this->doRequestRoute($this->getViewRoute());
        $nodeValues = $crawler->filter('.grid-time-column > div:first-child')->each(function ($node, $i) {
            return (int) substr($node->text(), 0, strlen($node->text() - 1));
        });
        foreach ($nodeValues as $value) {
            $this->assertTrue(
                is_numeric($value),
                'Hour not numeric found.'
            );
            $this->assertTrue(
                $value >= 0 && $value < 24,
                "Hour $value not in the range 0<->23."
            );
        }
    }

    public function testMinutesConsistencyViewAction()
    {
        $crawler = $this->doRequestRoute($this->getViewRoute());
        $nodeValues = $crawler->filter('.grid-time-column > div:not(:first-child)')->each(function ($node, $i) {
            $count = preg_match('/^([\d]+)/', $node->text(), $matches);
            if ($count == 1) {
                return (int) $matches[0];
            } else {
                return false;
            }
        });
        foreach ($nodeValues as $value) {
            $this->assertTrue(
                is_numeric($value),
                'Minute not numeric found.'
            );
            $this->assertTrue(
                $value >= 0 && $value < 60,
                "Minute $value not in the range 0<->59."
            );
        }
    }

    public function testExceptionsViewAction()
    {
        $crawler = $this->doRequestRoute($this->getViewRoute());

        $this->assertTrue(
            $crawler->filter('html:contains("Sauf le 09/05/2014")')->count() > 0,
            "the exception value was not found in html."
        );
        $this->assertTrue(
            $crawler->filter('html:contains("Y compris le 09/05/2014")')->count() > 0,
            "the exception value was not found in html."
        );
    }

    public function testFootNotesConsistencyViewAction()
    {
        $crawler = $this->doRequestRoute($this->getViewRoute());

        $this->assertTrue(
            $crawler->filter('html:contains("au plus tard la veille du déplacement du lundi au vendredi de 9h à 12h30 et de 13h30 à 16h30.")')->count() > 0,
            "the note value was not found in html."
        );

        $this->assertTrue(
            $crawler->filter(
                'html:contains("au plus tard la veille du déplacement du lundi au vendredi de 9h à 12h30 et de 13h30 à 16h30.")'
            )->count() == 1,
            "the note value was found in html more than once."
        );

        $this->assertTrue(
            $crawler->filter(
                '.tab-content > .tab-pane:first-child .notes-wrapper > div:not(:first-child)'
            )->count() == 4,
            "Expected 4 notes label, found " . $crawler->filter('.tab-content > .tab-pane:first-child .notes-wrapper > div:not(:first-child)')->count()
        );

        $notesLabels = $crawler
            ->filter(
                '.tab-content > .tab-pane:first-child .notes-wrapper > div:not(:first-child) > span.bold'
            )->each(function ($node, $i) {
                return $node->text();
            });

        $asciiStart = 97;
        foreach ($notesLabels as $label) {
            $this->assertTrue(ord($label) == $asciiStart, "Note label $label should be " . chr($asciiStart));
            $asciiStart++;
        }
        // check if we find consistent note in timegrid
        $notes = $crawler->filter('.grid-time-column > div:not(:first-child)')->each(function ($node, $i) {
            $count = preg_match('/^[\d]+([a-z]{1})/', $node->text(), $matches);
            if ($count == 1) {
                return $matches[1];
            }
        });

        foreach ($notes as $note) {
            if (!empty($note)) {
                $this->assertTrue(in_array($note, $notesLabels), "Found note label $note in timegrid not present in notes wrapper.");
            }
        }
    }

    public function testStopPointCodeBlock()
    {
        $translator = $this->client->getContainer()->get('translator');
        $season = $this->getRepository('CanalTPMttBundle:Season')->find(1);

        $crawler = $this->doRequestRoute($this->getViewRoute());
        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("MERMB-1")')->count(),
            "Stop point code (external code) not found in stop point timetable view page"
        );
    }
}
