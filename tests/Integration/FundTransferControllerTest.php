<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class FundTransferControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testHealthCheck(): void
    {
        $this->client->request('GET', '/api/health');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('healthy', $data['status']);
        $this->assertArrayHasKey('timestamp', $data);
    }

    public function testSuccessfulTransfer(): void
    {
        $transferData = [
            'fromAccountNumber' => 'ACC1000000001',
            'toAccountNumber' => 'ACC1000000002',
            'amount' => '100.50',
            'description' => 'Test transfer'
        ];

        $this->client->request(
            'POST',
            '/api/transfers',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($transferData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('transaction_id', $data['data']);
        $this->assertEquals('ACC1000000001', $data['data']['from_account']);
        $this->assertEquals('ACC1000000002', $data['data']['to_account']);
        $this->assertEquals('100.50', $data['data']['amount']);
        $this->assertEquals('completed', $data['data']['status']);
    }

    public function testInsufficientFunds(): void
    {
        $transferData = [
            'fromAccountNumber' => 'ACC1000000005', // Account with 0 balance
            'toAccountNumber' => 'ACC1000000001',
            'amount' => '100.00',
            'description' => 'Test insufficient funds'
        ];

        $this->client->request(
            'POST',
            '/api/transfers',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($transferData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Insufficient funds', $data['error']);
    }

    public function testAccountNotFound(): void
    {
        $transferData = [
            'fromAccountNumber' => 'INVALID_ACCOUNT',
            'toAccountNumber' => 'ACC1000000001',
            'amount' => '50.00',
            'description' => 'Test invalid account'
        ];

        $this->client->request(
            'POST',
            '/api/transfers',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($transferData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Account not found', $data['error']);
    }

    public function testSameAccountTransfer(): void
    {
        $transferData = [
            'fromAccountNumber' => 'ACC1000000001',
            'toAccountNumber' => 'ACC1000000001',
            'amount' => '50.00',
            'description' => 'Test same account transfer'
        ];

        $this->client->request(
            'POST',
            '/api/transfers',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($transferData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('same account', $data['error']);
    }

    public function testInvalidAmount(): void
    {
        $transferData = [
            'fromAccountNumber' => 'ACC1000000001',
            'toAccountNumber' => 'ACC1000000002',
            'amount' => '-50.00',
            'description' => 'Test negative amount'
        ];

        $this->client->request(
            'POST',
            '/api/transfers',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($transferData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function testGetTransactionDetails(): void
    {
        // First create a transaction
        $transferData = [
            'fromAccountNumber' => 'ACC1000000001',
            'toAccountNumber' => 'ACC1000000002',
            'amount' => '75.25',
            'description' => 'Test transaction details'
        ];

        $this->client->request(
            'POST',
            '/api/transfers',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($transferData)
        );

        $createData = json_decode($this->client->getResponse()->getContent(), true);
        $transactionId = $createData['data']['transaction_id'];

        // Now retrieve it
        $this->client->request('GET', '/api/transfers/' . $transactionId);
        
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals($transactionId, $data['data']['transaction_id']);
        $this->assertEquals('75.25', $data['data']['amount']);
    }

    public function testGetAccountTransfers(): void
    {
        $accountNumber = 'ACC1000000001';
        
        $this->client->request('GET', '/api/accounts/' . $accountNumber . '/transfers');
        
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertIsArray($data['data']);
    }

    public function testValidationErrors(): void
    {
        $invalidData = [
            'fromAccountNumber' => '', // Empty
            'toAccountNumber' => 'ACC1000000002',
            'amount' => 'invalid',
        ];

        $this->client->request(
            'POST',
            '/api/transfers',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($invalidData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
    }
}
