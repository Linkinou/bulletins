<?php

namespace Tests\Feature;

use App\Report;
use Tests\TestCase;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportsHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function testRedirectedOnIndex()
    {
        $response = $this->get(route('reports.choose'));

        $response->assertRedirect(route('reports.index', $this->projects()[0]));
    }

    public function testIndexNotLoggedIn()
    {
        $this->get(route('reports.index', $this->projects()[0]))->assertRedirect(route('login'));
    }

    public function testIndexLoggedIn()
    {
        $this
            ->withSession(['logged_in' => true])
            ->get(route('reports.index', $this->projects()[0]))->assertStatus(404);

        $dummy = factory(Report::class)->create(['project' => $this->projects()[1]]);

        $report = factory(Report::class)->create(['project' => $this->projects()[0]]);
        $response = $this
            ->withSession(['logged_in' => true])
            ->get(route('reports.index', $report->project));

        $response
            ->assertStatus(200)
            ->assertViewIs('reports.index')
            ->assertViewHas('projects', collect([$dummy->project, $report->project])->sort()->values())
            ->assertViewHas('shareUrl', URL::signedRoute('reports.index', $report->project))
            ->assertViewHas('reports', Report::where('project', $report->project)->get());
    }

    public function testIndexWithSignature()
    {
        $report = factory(Report::class)->create();

        $this->get(URL::signedRoute('reports.index', $report->project))->assertOk();
    }

    private function projects()
    {
        return config('app.projects');
    }
}