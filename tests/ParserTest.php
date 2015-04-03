<?php

namespace BitWasp\Buffertools\Tests;

use \BitWasp\Buffertools\Buffer;
use \BitWasp\Buffertools\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BitWasp\Buffertools\Parser
     */
    protected $parser;

    /**
     * @var string
     */
    protected $parserType;

    /**
     * @var string
     */
    protected $bufferType;

    public function __construct()
    {
        $this->parserType = 'BitWasp\Buffertools\Parser';
        $this->bufferType = 'BitWasp\Buffertools\Buffer';
    }

    public function setUp()
    {
        $this->parser = new Parser();
    }

    public function testParserEmpty()
    {
        $parser = new Parser();
        $this->assertInstanceOf($this->parserType, $parser);

        $this->assertSame(0, $this->parser->getPosition());
        $this->assertInstanceOf($this->bufferType, $this->parser->getBuffer());
        $this->assertEmpty($this->parser->getBuffer()->getHex());
    }

    public function testCreatesInstance()
    {
        $buffer = Buffer::hex('41414141');
        $this->parser = new Parser($buffer);
    }

    /**
     * @depends testCreatesInstance
     */
    public function testGetBuffer()
    {
        $buffer = Buffer::hex('41414141');

        $this->parser = new Parser($buffer);
        $this->assertSame($this->parser->getBuffer()->getBinary(), $buffer->getBinary());
    }

    /**
     *
     */
    public function testGetBufferEmptyNull()
    {
        $buffer = new Buffer();
        $this->parser = new Parser($buffer);
        $parserData = $this->parser->getBuffer()->getBinary();
        $bufferData = $buffer->getBinary();
        $this->assertSame($parserData, $bufferData);
    }

    public function testWriteBytes()
    {
        $bytes = '41424344';
        $parser = new Parser();
        $parser->writeBytes(4, Buffer::hex($bytes));
        $returned = $parser->getBuffer()->getHex();
        $this->assertSame($returned, '41424344');
    }

    public function testWriteBytesFlip()
    {
        $bytes = '41424344';
        $parser = new Parser();
        $parser->writeBytes(4, Buffer::hex($bytes), true);
        $returned = $parser->getBuffer()->getHex();
        $this->assertSame($returned, '44434241');
    }

    public function testReadBytes()
    {
        $bytes  = '41424344';
        $parser = new Parser($bytes);
        $read   = $parser->readBytes(4);
        $this->assertInstanceOf($this->bufferType, $read);
        $hex    = $read->getHex();
        $this->assertSame($bytes, $hex);
    }

    public function testReadBytesFlip()
    {
        $bytes  = '41424344';
        $parser = new Parser($bytes);
        $read   = $parser->readBytes(4, true);
        $this->assertInstanceOf($this->bufferType, $read);
        $hex    = $read->getHex();
        $this->assertSame('44434241', $hex);
    }

    public function testReadBytesEmpty()
    {
        // Should return false because position is zero,
        // and length is zero.

        $parser = new Parser();
        $data = $parser->readBytes(0);
        $this->assertFalse(!!$data);
    }

    public function testReadBytesEndOfString()
    {
        $parser = new Parser('4041414142414141');
        $bytes1 = $parser->readBytes(4);
        $bytes2 = $parser->readBytes(4);
        $this->assertSame($bytes1->getHex(), '40414141');
        $this->assertSame($bytes2->getHex(), '42414141');
        $this->assertFalse(!!$parser->readBytes(1));
    }

    /**
     * @expectedException \Exception
     */
    public function testReadBytesBeyondLength()
    {
        $bytes = '41424344';
        $parser = new Parser($bytes);
        $parser->readBytes(5);
    }

    public function testParseBytes()
    {
        $bytes  = '4142434445464748';
        $parser = new Parser($bytes);
        $bs1    = $parser->parseBytes(1);
        $bs2    = $parser->parseBytes(2);
        $bs3    = $parser->parseBytes(4);
        $bs4    = $parser->parseBytes(1);
        $this->assertInstanceOf($this->parserType, $bs1);
        $this->assertSame('41', $bs1->getBuffer()->getHex());
        $this->assertInstanceOf($this->parserType, $bs2);
        $this->assertSame('4243', $bs2->getBuffer()->getHex());
        $this->assertInstanceOf($this->parserType, $bs3);
        $this->assertSame('44454647', $bs3->getBuffer()->getHex());
        $this->assertInstanceOf($this->parserType, $bs4);
        $this->assertSame('48', $bs4->getBuffer()->getHex());
    }

    public function testWriteWithLength()
    {
        $str1 = Buffer::hex('01020304050607080909');
        $parser1 = new Parser();
        $parser1->writeWithLength($str1);
        $this->assertSame('0a', $parser1->readBytes(1)->getHex());
        $this->assertSame('01020304050607080909', $parser1->readBytes(10)->getHex());

        $str2 = Buffer::hex('00010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102');
        $parser2 = new Parser();
        $parser2->writeWithLength($str2);
        $this->assertSame('fdfd00', $parser2->readBytes(3)->getHex());
        $this->assertSame('00010203040506070809', $parser2->readBytes(10)->getHex());

    }

    public function testGetVarInt()
    {
        $p1 = new Parser('0141');
        $this->assertSame('01', $p1->getVarInt()->getHex());
        $this->assertSame('41', $p1->readBytes(1)->getHex());
        $this->assertSame(false, $p1->readBytes(1));

        $p2 = new Parser('022345');
        $this->assertSame('02', $p2->getVarInt()->getHex());
        $this->assertSame('2345', $p2->readBytes(2)->getHex());
        $this->assertSame(false, $p2->readBytes(1));

        $s3 = Buffer::hex('00010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102');
        $p3 = new Parser();
        $p3->writeWithLength($s3);
        $p3 = new Parser($p3->getBuffer());
        $this->assertSame('253', $p3->getVarInt()->getInt());
    }

    public function testGetVarString()
    {
        $strings = array(
            '',
            '00',
            '00010203040506070809',
            '00010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102030405060708090001020304050607080900010203040506070809000102'
        );

        foreach ($strings as $string) {
            $p = new Parser();
            $p->writeWithLength(Buffer::hex($string));
            $np = new Parser($p->getBuffer());
            $this->assertSame($string, $np->getVarString()->getHex());
        }
    }

    public function testGetArray()
    {
        /** @var Buffer[] $expected */
        $expected = array(
            Buffer::hex('09020304'),
            Buffer::hex('08020304'),
            Buffer::hex('07020304')
        );

        $parser = new Parser(Buffer::hex('03090203040802030407020304'));
        $callback = function() use (&$parser) {
            return $parser->readBytes(4);
        };

        /** @var Buffer[] $expected */
        $actual = $parser->getArray($callback);

        for ($i = 0; $i < count($expected); $i++) {
            $this->assertEquals($expected[$i]->getBinary(), $actual[$i]->getBinary());
        }
    }

    public function testWriteArray()
    {
        $this->markTestSkipped("@TODO: tests which don't rely on bitcoin-php classes");

        $transaction = TransactionFactory::create();
        $input  = new TransactionInput('0000000000000000000000000000000000000000000000000000000000000000', 0);
        $output = new TransactionOutput(1, null);
        $transaction
            ->getInputs()
            ->addInput($input);

        $transaction
            ->getOutputs()
            ->addOutput($output);

        $array  = new TransactionCollection(array($transaction, $transaction));
        $parser = new Parser();
        $parser->writeArray($array->getBuffer());

        $this->assertSame('010000000100000000000000000000000000000000000000000000000000000000000000000000000000ffffffff0101000000000000000000000000', $transaction->getBuffer()->getHex());
        $this->assertSame('02010000000100000000000000000000000000000000000000000000000000000000000000000000000000ffffffff0101000000000000000000000000010000000100000000000000000000000000000000000000000000000000000000000000000000000000ffffffff0101000000000000000000000000', $parser->getBuffer()->getHex());

    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWriteArrayFailure()
    {
        $this->markTestSkipped("@TODO: tests which don't rely on bitcoin-php classes");

        $network = new Network('00','05','80');
        $array = array($network);

        $parser = new Parser();
        $parser->writeArray($array);
    }
} 
