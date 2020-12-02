<?php

namespace Remp\MailerModule\Components;

use Remp\MailerModule\Repository\SourceTemplatesRepository;

class GeneratorWidgets extends BaseControl
{
    private $templateName = 'generator_widgets.latte';

    private $sourceTemplateId;

    private $sourceTemplatesRepository;

    private $widgetsManager;

    public function __construct(
        int $sourceTemplateId,
        GeneratorWidgetsManager $widgetsManager,
        SourceTemplatesRepository $sourceTemplatesRepository
    ) {
        parent::__construct();

        $this->sourceTemplateId = $sourceTemplateId;
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
        $this->widgetsManager = $widgetsManager;
    }

    protected function attached($presenter): void
    {
        parent::attached($presenter);

        $allWidgets = $this->widgetsManager->getAllWidgets();
        foreach ($allWidgets as $generator => $widgets) {
            foreach ($widgets as $widget) {
                if (!$this->getComponent($widget->identifier(), false)) {
                    $this->addComponent($widget, $widget->identifier());
                }
            }
        }
    }

    public function render(array $params): void
    {
        $template = $this->sourceTemplatesRepository->find($this->sourceTemplateId);
        $widgets = $this->widgetsManager->getWidgets($template->generator);

        $this->template->widgets = $widgets;
        $this->template->params = $params;

        $this->template->setFile(__DIR__ . '/' . $this->templateName);
        $this->template->render();
    }
}
