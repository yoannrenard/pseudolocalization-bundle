<?php

namespace YoannRenard\PseudolocalizationBundle\Tests\Command;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use YoannRenard\PseudolocalizationBundle\Command\GenerateCommand;
use PHPUnit\Framework\TestCase;

class GenerateCommandTest extends TestCase
{
    /** @var CommandTester */
    private $tester;

    /** @var KernelInterface|ObjectProphecy*/
    private $kernelMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $application = new Application();
        $application->add(new GenerateCommand('fr', __DIR__.'/../dummy/Resources/translations'));
        $command = $application->find('translation:pseudolocalization:generate');
        $command->setApplication($application);

        $this->tester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function it_displays_a_success_message()
    {
        $this->tester->execute([
            'command' => 'translation:pseudolocalization:generate',
        ]);

        $this->assertRegExp('/Your translation files were successfully updated./', $this->tester->getDisplay());
        $this->assertEquals(0, $this->tester->getStatusCode());
    }
}
