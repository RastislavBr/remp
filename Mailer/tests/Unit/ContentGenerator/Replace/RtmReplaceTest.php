<?php
declare(strict_types=1);

namespace Tests\Unit\ContentGenerator\Replace;

use PHPUnit\Framework\TestCase;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;
use Remp\MailerModule\Models\ContentGenerator\Replace\AnchorRtmReplace;
use Remp\MailerModule\Models\DataRow;

class RtmReplaceTest extends TestCase
{
    /** @var AnchorRtmReplace */
    protected $rtmReplace;

    protected $generatorInput;

    public function setUp(): void
    {
        $this->rtmReplace = new AnchorRtmReplace();

        $mailType = new DataRow([
            'id' => 1,
            'name' => 'type',
            'code' => 'demo-weekly-newsletter'
        ]);

        $mailLayout = new DataRow([
            'id' => 1,
            'name' => "layout1",
        ]);

        $mailTemplate = new DataRow([
            'name' => 'Tesst',
            'code' => 'impresa_mail_20190903103350',
            'description' => '',
            'from' => 'from',
            'autologin' => true,
            'subject' => 'subject',
            'mail_body_text' => 'textt',
            'mail_body_html' => 'html',
            'mail_layout_id' => $mailLayout->id,
            'mail_layout' => $mailLayout,
            'mail_type_id' => $mailType->id,
            'mail_type' => $mailType
        ]);

        $this->generatorInput = new GeneratorInput($mailTemplate);
    }

    public function testAddUtmParametersWhenTheUrlDoesNotContainAnyRtm()
    {
        $content = $this->rtmReplace->replace('<a href="https://expresso.pt/html/que-nao-entrou-em-acao" target="blank"/>', $this->generatorInput);
        $this->assertEquals(
            '<a href="https://expresso.pt/html/que-nao-entrou-em-acao?rtm_source=demo-weekly-newsletter&rtm_medium=email&rtm_campaign=impresa_mail_20190903103350&rtm_content=" target="blank"/>',
            $content
        );
    }

    public function testReplaceExistingRtmParametersWhenTheUrlAlreadyContainsTheseUtm()
    {
        $content = $this->rtmReplace->replace('<a href="https://expresso.pt/html/que-nao-entrou-em-acao?rtm_content=apple&amp;feira=terca&modelo=1?a=1&b=2" target="blank"/>', $this->generatorInput);
        $this->assertEquals(
            '<a href="https://expresso.pt/html/que-nao-entrou-em-acao?rtm_content=&feira=terca&modelo=1%3Fa%3D1&b=2&rtm_source=demo-weekly-newsletter&rtm_medium=email&rtm_campaign=impresa_mail_20190903103350" target="blank"/>',
            $content
        );
    }

    public function testFixHTMLEntitiesFromURL()
    {
        $content = $this->rtmReplace->replace('<a href="https://expresso.pt/html/que-nao-entrou-em-acao?rtm_content=apple&amp;feira=terca&modelo=1?a=1&b=2" target="blank"/>', $this->generatorInput);
        $this->assertEquals(
            '<a href="https://expresso.pt/html/que-nao-entrou-em-acao?rtm_content=&feira=terca&modelo=1%3Fa%3D1&b=2&rtm_source=demo-weekly-newsletter&rtm_medium=email&rtm_campaign=impresa_mail_20190903103350" target="blank"/>',
            $content
        );
    }

    public function testPreserveTheParametersThatIsNotRtm()
    {
        $content = $this->rtmReplace->replace('<a href="https://expresso.pt/html/que-nao-entrou-em-acao?feira=terca&modelo=1&a=1&b=2" target="blank"/>', $this->generatorInput);
        $this->assertEquals(
            '<a href="https://expresso.pt/html/que-nao-entrou-em-acao?feira=terca&modelo=1&a=1&b=2&rtm_source=demo-weekly-newsletter&rtm_medium=email&rtm_campaign=impresa_mail_20190903103350&rtm_content=" target="blank"/>',
            $content
        );
    }

    public function testPreserveAdditionalAnchorAttributes()
    {
        $content = $this->rtmReplace->replace('<a style="color: red" data-foo="bar" href="https://expresso.pt/" target="blank"/>', $this->generatorInput);
        $this->assertEquals(
            '<a style="color: red" data-foo="bar" href="https://expresso.pt/?rtm_source=demo-weekly-newsletter&rtm_medium=email&rtm_campaign=impresa_mail_20190903103350&rtm_content=" target="blank"/>',
            $content
        );
    }

    public function testPreserveMailtoLinks()
    {
        $content = $this->rtmReplace->replace('<a href="mailto:admin@example.com"/>', $this->generatorInput);
        $this->assertEquals(
            '<a href="mailto:admin@example.com"/>',
            $content
        );
    }

    public function testPreserveMailgunVariablesInUrl()
    {
        $url = $this->rtmReplace->replaceUrl('https://predplatne.dennikn.sk/email-settings', $this->generatorInput);
        $this->assertEquals('https://predplatne.dennikn.sk/email-settings?rtm_source=demo-weekly-newsletter&rtm_medium=email&rtm_campaign=impresa_mail_20190903103350&rtm_content=', $url);

        $url = $this->rtmReplace->replaceUrl('https://predplatne.dennikn.sk/email-settings?%recipient.autologin%', $this->generatorInput);
        $this->assertEquals('https://predplatne.dennikn.sk/email-settings?%recipient.autologin%&rtm_source=demo-weekly-newsletter&rtm_medium=email&rtm_campaign=impresa_mail_20190903103350&rtm_content=', $url);
    }
}
