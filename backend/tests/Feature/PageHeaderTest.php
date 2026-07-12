<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageHeaderTest extends TestCase
{
    #[Test]
    public function it_renders_the_title(): void
    {
        $view = $this->blade('<x-page-header title="Dashboard" />');

        $view->assertSee('Dashboard');
    }

    #[Test]
    public function it_renders_description_when_provided(): void
    {
        $view = $this->blade(
            '<x-page-header title="Dashboard" description="A short description of this section." />'
        );

        $view->assertSee('Dashboard');
        $view->assertSee('A short description of this section.');
    }

    #[Test]
    public function it_does_not_render_description_when_not_provided(): void
    {
        $view = $this->blade('<x-page-header title="Dashboard" />');

        $view->assertSee('Dashboard');
        $view->assertDontSee('A short description of this section.');
    }

    #[Test]
    public function it_renders_action_slot_when_content_provided(): void
    {
        $view = $this->blade(
            '<x-page-header title="Categories">'
            . '<x-slot:action><a href="/categories/create">Create</a></x-slot:action>'
            . '</x-page-header>'
        );

        $view->assertSee('Categories');
        $view->assertSee('<a href="/categories/create">Create</a>', false);
    }

    #[Test]
    public function it_renders_without_action_when_slot_empty(): void
    {
        $view = $this->blade('<x-page-header title="Dashboard" />');

        $view->assertSee('Dashboard');
        $view->assertDontSee('<a', false);
    }
}
