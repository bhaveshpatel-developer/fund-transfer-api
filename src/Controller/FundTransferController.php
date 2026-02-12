<?php

namespace App\Controller;

use App\DTO\TransferRequest;
use App\Exception\AccountNotFoundException;
use App\Exception\InsufficientFundsException;
use App\Exception\InvalidTransferException;
use App\Service\FundTransferService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
class FundTransferController extends AbstractController
{
    public function __construct(
        private readonly FundTransferService $transferService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Transfer funds between accounts
     */
    #[Route('/transfers', name: 'transfer_funds', methods: ['POST'])]
    public function transfer(Request $request): JsonResponse
    {
        try {
            // Deserialize and validate request
            $transferRequest = $this->serializer->deserialize(
                $request->getContent(),
                TransferRequest::class,
                'json'
            );

            $errors = $this->validator->validate($transferRequest);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }

                return $this->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            // Execute transfer
            $transaction = $this->transferService->transfer($transferRequest);

            return $this->json([
                'success' => true,
                'data' => [
                    'transaction_id' => $transaction->getTransactionId(),
                    'from_account' => $transaction->getFromAccount()->getAccountNumber(),
                    'to_account' => $transaction->getToAccount()->getAccountNumber(),
                    'amount' => $transaction->getAmount(),
                    'currency' => $transaction->getCurrency(),
                    'status' => $transaction->getStatus(),
                    'description' => $transaction->getDescription(),
                    'created_at' => $transaction->getCreatedAt()->format('Y-m-d H:i:s'),
                    'completed_at' => $transaction->getCompletedAt()?->format('Y-m-d H:i:s'),
                ]
            ], Response::HTTP_CREATED);

        } catch (AccountNotFoundException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);

        } catch (InsufficientFundsException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (InvalidTransferException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);

        } catch (\Throwable $e) {
            $this->logger->error('Transfer error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'error' => 'An error occurred while processing the transfer'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get transaction details
     */
    #[Route('/transfers/{transactionId}', name: 'get_transfer', methods: ['GET'])]
    public function getTransfer(string $transactionId): JsonResponse
    {
        $transaction = $this->transferService->getTransaction($transactionId);

        if (!$transaction) {
            return $this->json([
                'success' => false,
                'error' => 'Transaction not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'transaction_id' => $transaction->getTransactionId(),
                'from_account' => $transaction->getFromAccount()->getAccountNumber(),
                'to_account' => $transaction->getToAccount()->getAccountNumber(),
                'amount' => $transaction->getAmount(),
                'currency' => $transaction->getCurrency(),
                'status' => $transaction->getStatus(),
                'description' => $transaction->getDescription(),
                'failure_reason' => $transaction->getFailureReason(),
                'created_at' => $transaction->getCreatedAt()->format('Y-m-d H:i:s'),
                'completed_at' => $transaction->getCompletedAt()?->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Get account transaction history
     */
    #[Route('/accounts/{accountNumber}/transfers', name: 'account_transfers', methods: ['GET'])]
    public function getAccountTransfers(string $accountNumber, Request $request): JsonResponse
    {
        $limit = min((int) $request->query->get('limit', 50), 100);
        $transactions = $this->transferService->getAccountTransactions($accountNumber, $limit);

        $data = array_map(function ($transaction) use ($accountNumber) {
            return [
                'transaction_id' => $transaction->getTransactionId(),
                'type' => $transaction->getFromAccount()->getAccountNumber() === $accountNumber ? 'debit' : 'credit',
                'from_account' => $transaction->getFromAccount()->getAccountNumber(),
                'to_account' => $transaction->getToAccount()->getAccountNumber(),
                'amount' => $transaction->getAmount(),
                'currency' => $transaction->getCurrency(),
                'status' => $transaction->getStatus(),
                'description' => $transaction->getDescription(),
                'created_at' => $transaction->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $transactions);

        return $this->json([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ]);
    }

    /**
     * Health check endpoint
     */
    #[Route('/health', name: 'health_check', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        return $this->json([
            'status' => 'healthy',
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
        ]);
    }
}
