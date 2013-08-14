<?php

namespace Behat\Behat\Formatter;

use Behat\Behat\Definition\DefinitionInterface,
    Behat\Behat\DataCollector\LoggerDataCollector,
    Behat\Behat\Definition\DefinitionSnippet,
    Behat\Behat\Exception\UndefinedException;

use Behat\Gherkin\Node\AbstractNode,
    Behat\Gherkin\Node\FeatureNode,
    Behat\Gherkin\Node\BackgroundNode,
    Behat\Gherkin\Node\AbstractScenarioNode,
    Behat\Gherkin\Node\OutlineNode,
    Behat\Gherkin\Node\ScenarioNode,
    Behat\Gherkin\Node\StepNode,
    Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * XML formatter.
 *
 * @author Michal Przytulski <michal@przytulski.pl>
 */
class XmlFormatter extends PrettyFormatter
{
    /**
     * Deffered footer template part.
     *
     * @var string
     */
    protected $footer;

    protected $indent = 0;

    /**
     * {@inheritdoc}
     */
    protected function getDefaultParameters()
    {
        return array(
            'template_path' => null
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function printSuiteHeader(LoggerDataCollector $logger)
    {
        $this->parameters->set('decorated', false);

        $template = $this->getXmlTemplate();
        $header         = mb_substr($template, 0, mb_strpos($template, '{{content}}'));
        $this->footer   = mb_substr($template, mb_strpos($template, '{{content}}') + 11);

        $this->writeln($header);
    }

    /**
     * {@inheritdoc}
     */
    protected function printSuiteFooter(LoggerDataCollector $logger)
    {
        $this->printSummary($logger);
        $this->writeln($this->footer);
    }

    /**
     * {@inheritdoc}
     */
    protected function printFeatureHeader(FeatureNode $feature)
    {
        $this->indent++;

        $this->writelnWithIndent(
            sprintf(
                '<feature keyword="%s">',
                $feature->getKeyword()
            )
        );


        $this->printFeatureOrScenarioTags($feature);
        $this->indent++;
        $this->printFeatureName($feature);
        $this->indent--;

        if (null !== $feature->getDescription()) {
            $this->printFeatureDescription($feature);
        }
        $this->indent++;
    }

    /**
     * {@inheritdoc}
     */
    protected function printFeatureOrScenarioTags(AbstractNode $node)
    {
        if (count($tags = $node->getOwnTags())) {
            $this->writelnWithIndent('<tags>');
            $this->indent++;
            foreach ($tags as $tag) {
                $this->writelnWithIndent(sprintf('<tag>%s</tag>', $tag));
            }
            $this->indent--;
            $this->writelnWithIndent('</tags>');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function printFeatureName(FeatureNode $feature)
    {l;
        $this->writelnWithIndent(
            sprintf(
                '<title>%s</title>',
                $feature->getTitle()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function printFeatureDescription(FeatureNode $feature)
    {
        $lines = explode("\n", $feature->getDescription());

        $this->indent++;

        $this->writelnWithIndent('<description>');

        $this->indent++;

        foreach ($lines as $line) {
            $this->writelnWithIndent(htmlspecialchars($line));
        }

        $this->indent--;

        $this->writelnWithIndent('</description>');

        $this->indent--;
    }

    /**
     * {@inheritdoc}
     */
    protected function printFeatureFooter(FeatureNode $feature)
    {
        $this->indent--;
        $this->writelnWithIndent('</feature>');
    }

    /**
     * {@inheritdoc}
     */
    protected function printBackgroundHeader(BackgroundNode $background)
    {
        $this->writelnWithIndent(
            sprintf(
                '<background keyword="%s">',
                $background->getKeyword()
            )
        );

        $this->printScenarioName($background, 'item');
    }

    /**
     * {@inheritdoc}
     */
    protected function printBackgroundFooter(BackgroundNode $background)
    {
        $this->writelnWithIndent('</background>');
    }

    /**
     * {@inheritdoc}
     */
    protected function printScenarioHeader(ScenarioNode $scenario)
    {
        $this->writelnWithIndent(
            sprintf(
                '<scenario keyword="%s">',
                $scenario->getKeyword()
            )
        );

        $this->printFeatureOrScenarioTags($scenario);
        $this->printScenarioName($scenario);
    }

    /**
     * {@inheritdoc}
     */
    protected function printScenarioName(AbstractScenarioNode $scenario, $type = 'scenario')
    {

        if ($scenario->getTitle()) {
            $this->indent++;
            $this->writelnWithIndent(sprintf('<title>%s</title>', $scenario->getTitle()));
            $this->indent--;
        }

        $this->indent++;
        $this->printScenarioPath($scenario);
        $this->indent--;

    }

    /**
     * {@inheritdoc}
     */
    protected function printScenarioFooter(ScenarioNode $scenario)
    {
        $this->writelnWithIndent('</scenario>');
    }

    protected function printScenarioPath(AbstractScenarioNode $scenario)
    {
        if ($this->getParameter('paths')) {
            $lines       = explode("\n", $this->getFeatureOrScenarioName($scenario));
            $nameLength  = mb_strlen(current($lines));
            $indentCount = $nameLength > $this->maxLineLength ? 0 : $this->maxLineLength - $nameLength;

            $this->printPathComment(
                $this->relativizePathsInString($scenario->getFile()),
                $scenario->getLine()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function printOutlineHeader(OutlineNode $outline)
    {
        $this->writelnWithIndent('<outline>');

        $this->printFeatureOrScenarioTags($outline);
        $this->printScenarioName($outline);
    }

    /**
     * {@inheritdoc}
     */
    protected function printOutlineSteps(OutlineNode $outline)
    {
        parent::printOutlineSteps($outline);
        $this->writeln('</ol>');
    }

    /**
     * {@inheritdoc}
     */
    protected function printOutlineExamplesSectionHeader(TableNode $examples)
    {
        $this->writeln('<div class="examples">');

        if (!$this->getParameter('expand')) {
            $this->writeln('<h4>' . $examples->getKeyword() . '</h4>');
            $this->writeln('<table>');
            $this->writeln('<thead>');
            $this->printColorizedTableRow($examples->getRow(0), 'skipped');
            $this->writeln('</thead>');
            $this->writeln('<tbody>');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function printOutlineExampleResult(TableNode $examples, $iteration, $result, $isSkipped)
    {
        if (!$this->getParameter('expand')) {
            $color  = $this->getResultColorCode($result);

            $this->printColorizedTableRow($examples->getRow($iteration + 1), $color);
            $this->printOutlineExampleResultExceptions($examples, $this->delayedStepEvents);
        } else {
            $this->write('<h4>' . $examples->getKeyword() . ': ');
            foreach ($examples->getRow($iteration + 1) as $value) {
                $this->write('<span>' . $value . '</span>');
            }
            $this->writeln('</h4>');

            foreach ($this->delayedStepEvents as $event) {
                $this->writeln('<ol>');
                $this->printStep(
                    $event->getStep(),
                    $event->getResult(),
                    $event->getDefinition(),
                    $event->getSnippet(),
                    $event->getException()
                );
                $this->writeln('</ol>');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function printOutlineExampleResultExceptions(TableNode $examples, array $events)
    {
        $colCount = count($examples->getRow(0));

        foreach ($events as $event) {
            $exception = $event->getException();
            if ($exception && !$exception instanceof UndefinedException) {
                $error = $this->exceptionToString($exception);
                $error = $this->relativizePathsInString($error);

                $this->writeln('<tr class="failed exception">');
                $this->writeln('<td colspan="' . $colCount . '">');
                $this->writeln('<pre class="backtrace">' . htmlspecialchars($error) . '</pre>');
                $this->writeln('</td>');
                $this->writeln('</tr>');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function printOutlineFooter(OutlineNode $outline)
    {
        if (!$this->getParameter('expand')) {
            $this->writeln('</tbody>');
            $this->writeln('</table>');
        }
        $this->writeln('</div>');
        $this->writeln('</div>');
    }

    /**
     * {@inheritdoc}
     */
    protected function printStep(StepNode $step, $result, DefinitionInterface $definition = null,
                                 $snippet = null, \Exception $exception = null)
    {
        $this->indent++;
        $this->writelnWithIndent(
            sprintf(
                '<step status="%s" keyword="%s">',
                $this->getResultColorCode($result),
                $step->getType()
            )
        );

        $this->indent++;


        $color = $this->getResultColorCode($result);

        $this->printStepBlock($step, $definition, $color);

        if ($this->parameters->get('multiline_arguments')) {
            $this->printStepArguments($step->getArguments(), $color);
        }
        if (null !== $exception &&
            (!$exception instanceof UndefinedException || null === $snippet)) {
            $this->printStepException($exception, $color);
        }
        if (null !== $snippet && $this->getParameter('snippets')) {
            $this->printStepSnippet($snippet);
        }

        $this->indent--;

        //parent::printStep($step, $result, $definition, $snippet, $exception);

        $this->writelnWithIndent('</step>');
        $this->indent--;
    }

    protected function printStepArguments(array $arguments, $color)
    {
        if (count($arguments) == 0) {
            $this->writelnWithIndent('<arguments />');

            return;
        }

        $this->writelnWithIndent('<arguments>');
        $this->indent++;

        foreach ($arguments as $argument) {
            if ($argument instanceof PyStringNode) {
                $this->printStepPyStringArgument($argument, $color);
            } elseif ($argument instanceof TableNode) {
                $this->printStepTableArgument($argument, $color);
            }
        }

        $this->indent--;
        $this->writelnWithIndent('</arguments>');
    }

    /**
     * {@inheritdoc}
     */
    protected function printStepBlock(StepNode $step, DefinitionInterface $definition = null, $color)
    {
        $this->printStepName($step, $definition, $color);
        if (null !== $definition) {
            $this->printStepDefinitionPath($step, $definition);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function printStepName(StepNode $step, DefinitionInterface $definition = null, $color)
    {
        $type   = $step->getType();
        $text   = $this->inOutlineSteps ? $step->getCleanText() : $step->getText();

        if (null !== $definition) {
            $text = $this->colorizeDefinitionArguments($text, $definition, $color);
        }

        $this->writelnWithIndent(
            sprintf(
                '<description>%s</description>',
                $text
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function printStepDefinitionPath(StepNode $step, DefinitionInterface $definition)
    {
        if ($this->getParameter('paths')) {
            if ($this->hasParameter('paths_base_url')) {
                $this->printPathLink($definition);
            } else {
                $this->printPathComment($this->relativizePathsInString($definition->getPath()));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function printStepPyStringArgument(PyStringNode $pystring, $color = null)
    {
        $this->writelnWithIndent(
            sprintf(
                "<argument>%s</argument>",
                htmlspecialchars((string) $pystring)
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function printStepTableArgument(TableNode $table, $color = null)
    {
        $this->writeln('<table class="argument">');

        $this->writeln('<thead>');
        $headers = $table->getRow(0);
        $this->printColorizedTableRow($headers, 'row');
        $this->writeln('</thead>');

        $this->writeln('<tbody>');
        foreach ($table->getHash() as $row) {
            $this->printColorizedTableRow($row, 'row');
        }
        $this->writeln('</tbody>');

        $this->writeln('</table>');
    }

    /**
     * {@inheritdoc}
     */
    protected function printStepException(\Exception $exception, $color)
    {
        $this->writelnWithIndent("<exception>");

        $error = $this->exceptionToString($exception);
        $error = $this->relativizePathsInString($error);

            $this->indent++;
            $this->writelnWithIndent("<backtrace>");
            $this->writelnWithIndent("<![CDATA[");
            foreach (explode("\n", $error) as $line) {
                if (empty($line)) {
                    continue;
                }
                $this->writeln(trim($line));
            }
            $this->writelnWithIndent("]]>");
            $this->writelnWithIndent("</backtrace>");

            $this->indent--;

        $this->writelnWithIndent("<exception>");
    }

    /**
     * {@inheritdoc}
     */
    protected function printStepSnippet(DefinitionSnippet $snippet)
    {
        $this->writelnWithIndent('<snippet">' . htmlspecialchars($snippet) . '</snippet>');
    }

    /**
     * {@inheritdoc}
     */
    protected function colorizeDefinitionArguments($text, DefinitionInterface $definition, $color)
    {
        $regex      = $definition->getRegex();
        $paramColor = $color . '_param';

        // If it's just a string - skip
        if ('/' !== substr($regex, 0, 1)) {
            return $text;
        }

        // Find arguments with offsets
        $matches = array();
        preg_match($regex, $text, $matches, PREG_OFFSET_CAPTURE);
        array_shift($matches);

        // Replace arguments with colorized ones
        $shift = 0;
        $lastReplacementPosition = 0;
        foreach ($matches as $key => $match) {
            if (!is_numeric($key) || -1 === $match[1] || false !== strpos($match[0], '<')) {
                continue;
            }

            $offset = $match[1] + $shift;
            $value  = $match[0];

            // Skip inner matches
            if ($lastReplacementPosition > $offset) {
                continue;
            }
            $lastReplacementPosition = $offset + strlen($value);

            $begin  = substr($text, 0, $offset);
            $end    = substr($text, $offset + strlen($value));
            $format = "{+strong class=\"$paramColor\"-}%s{+/strong-}";
            $text   = sprintf('%s'.$format.'%s', $begin, $value, $end);

            // Keep track of how many extra characters are added
            $shift += strlen($format) - 2;
            $lastReplacementPosition += strlen($format) - 2;
        }

        // Replace "<", ">" with colorized ones
        $text = preg_replace('/(<[^>]+>)/', "{+strong class=\"$paramColor\"-}\$1{+/strong-}", $text);
        $text = htmlspecialchars($text, ENT_NOQUOTES);
        $text = strtr($text, array('{+' => '<', '-}' => '>'));

        return $text;
    }

    /**
     * {@inheritdoc}
     */
    protected function printColorizedTableRow($row, $color)
    {
        $this->writeln('<tr class="' . $color . '">');

        foreach ($row as $column) {
            $this->writeln('<td>' . $column . '</td>');
        }

        $this->writeln('</tr>');
    }

    /**
     * Prints path link, which links to the source containing the step definition.
     *
     * @param DefinitionInterface $definition
     */
    protected function printPathLink(DefinitionInterface $definition)
    {
        $url = $this->getParameter('paths_base_url')
            . $this->relativizePathsInString($definition->getCallbackReflection()->getFileName());
        $path = $this->relativizePathsInString($definition->getPath());
        $this->writeln('<span class="path"><a href="' . $url . '">' . $path . '</a></span>');
    }

    /**
     * {@inheritdoc}
     */
    protected function printPathComment($path, $line = null)
    {

        if ($line == null) {
            list($context, $method) = explode('::', $path);
            $params = array('<location context="%s" method="%s" />', $context, $method);
        } else {
            $params = array('<location path="%s" line="%d" />', $path, $line);
        }

        $string = call_user_func_array('sprintf', $params);

        $this->writelnWithIndent($string);
    }

    /**
     * {@inheritdoc}
     */
    protected function printSummary(LoggerDataCollector $logger)
    {
        $results = $logger->getScenariosStatuses();
        $result = $results['failed'] > 0 ? 'failed' : 'passed';

        $this->writelnWithIndent(
            sprintf(
                '<summary status="%s" time="%f">',
                $result,
                $logger->getTotalTime()
            )
        );

        parent::printSummary($logger);

        $this->writelnWithIndent('</summary>');
    }

    /**
     * {@inheritdoc}
     */
    protected function printScenariosSummary(LoggerDataCollector $logger)
    {
        $statuses = $logger->getScenariosStatuses();

        $this->writelnWithIndent(
            sprintf(
                '<scenarios total="%d" passed="%d" skipped="%d" pending="%d" undefined="%d" failed="%d" />',
                $logger->getScenariosCount(),
                $statuses['passed'],
                $statuses['skipped'],
                $statuses['pending'],
                $statuses['undefined'],
                $statuses['failed']
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function printStepsSummary(LoggerDataCollector $logger)
    {
        $statuses = $logger->getStepsStatuses();

        $this->writelnWithIndent(
            sprintf(
                '<steps total="%d" passed="%d" skipped="%d" pending="%d" undefined="%d" failed="%d" />',
                $logger->getStepsCount(),
                $statuses['passed'],
                $statuses['skipped'],
                $statuses['pending'],
                $statuses['undefined'],
                $statuses['failed']
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function printTimeSummary(LoggerDataCollector $logger)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function printStatusesSummary(array $statusesStatistics)
    {
        $statuses = array();
        $statusTpl = '<strong class="%s">%s</strong>';
        foreach ($statusesStatistics as $status => $count) {
            if ($count) {
                $transStatus = $this->translateChoice(
                    "{$status}_count", $count, array('%1%' => $count)
                );
                $statuses[] = sprintf($statusTpl, $status, $transStatus);
            }
        }
        if (count($statuses)) {
            $this->writeln(' ('.implode(', ', $statuses).')');
        }
    }

    protected function writelnWithIndent($messages = '')
    {
        parent::writeln(
            sprintf(
                "%s%s",
                str_repeat("\t", $this->indent),
                $messages
            )
        );
    }

    /**
     * Get HTML template.
     *
     * @return string
     */
    protected function getXmlTemplate()
    {
        $templatePath = $this->parameters->get('template_path')
            ?: $this->parameters->get('support_path') . DIRECTORY_SEPARATOR . 'html.tpl';

        if (file_exists($templatePath)) {
            return file_get_contents($templatePath);
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<behat>
    {{content}}
</behat>
XML;
    }
}
