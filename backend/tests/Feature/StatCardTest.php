<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatCardTest extends TestCase
{
    #[Test]
    public function it_renders_the_label(): void
    {
        $view = $this->blade('<x-stat-card label="Ventas" />');

        $view->assertSee('Ventas');
    }

    #[Test]
    public function it_renders_as_link_when_href_provided(): void
    {
        $view = $this->blade(
            '<x-stat-card label="Ventas" href="/sales">$15,230</x-stat-card>'
        );

        $view->assertSee('Ventas');
        $view->assertSee('$15,230');
        $view->assertSee('<a', false);
        $view->assertSee('href="/sales"', false);
    }

    #[Test]
    public function it_renders_as_div_when_no_href(): void
    {
        $view = $this->blade('<x-stat-card label="Disponible">Online</x-stat-card>');

        $view->assertSee('Disponible');
        $view->assertSee('Online');
        $view->assertDontSee('<a', false);
    }

    #[Test]
    public function it_renders_slot_content(): void
    {
        $view = $this->blade(
            '<x-stat-card label="Productos">'
            . '<ul><li>45 activos</li></ul>'
            . '</x-stat-card>'
        );

        $view->assertSee('Productos');
        $view->assertSee('45 activos');
    }
}
