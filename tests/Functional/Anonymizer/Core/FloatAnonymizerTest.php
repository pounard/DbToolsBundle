<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Tests\FunctionalTestCase;

class FloatAnonymizerTest extends FunctionalTestCase
{
    /** @before */
    protected function createTestData(): void
    {
        $this->createOrReplaceTable(
            'table_test',
            [
                'id' => 'integer',
                'data' => 'float',
            ],
            [
                [
                    'id' => '1',
                    'data' => '1.5',
                ],
                [
                    'id' => '2',
                    'data' => '2.5',
                ],
                [
                    'id' => '3',
                    'data' => '3.5',
                ],
                [
                    'id' => '4',
                ],
            ],
        );
    }

    public function testAnonymize(): void
    {
        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'data',
            'float',
            new Options(['min' => 2, 'max' => 5, 'precision' => 6])
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(),
            $config
        );

        $this->assertSame(
            1.5,
            (float) $this->getConnection()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        foreach ($anonymizator->anonymize() as $message) {
        }

        $datas = $this->getConnection()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $data = (float) $datas[0];
        $this->assertNotNull($data);
        $this->assertNotSame(1.5, $data);
        $this->assertTrue($data >= 2 && $data <= 5);
        $this->assertSame($data, \round($data, 6));

        $data = (float) $datas[1];
        $this->assertNotNull($data);
        $this->assertNotSame(2.5, $data);
        $this->assertTrue($data >= 2 && $data <= 5);
        $this->assertSame($data, \round($data, 6));

        $data = (float) $datas[2];
        $this->assertNotNull($data);
        $this->assertNotSame(3.5, $data);
        $this->assertTrue($data >= 2 && $data <= 5);
        $this->assertSame($data, \round($data, 6));

        $this->assertNull($datas[3]);

        $this->assertCount(4, \array_unique($datas), 'All generated values are different.');
    }
}
