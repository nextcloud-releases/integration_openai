<?php

declare(strict_types=1);
namespace OCA\OpenAi\TextProcessing;

use OCA\OpenAi\AppInfo\Application;
use OCA\OpenAi\Service\OpenAiAPIService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\IProvider;
use Exception;


class FreePromptProvider implements IProvider {

	public function __construct(
		private OpenAiAPIService $openAiAPIService,
		private IConfig $config,
		private IL10N $l10n,
		private ?string $userId,
	) {
	}

	public function getName(): string {
		return $this->openAiAPIService->isUsingOpenAi()
			? $this->l10n->t('OpenAI integration')
			: $this->l10n->t('LocalAI integration');
	}

	public function process(string $prompt): string {
		$adminModel = $this->config->getAppValue(Application::APP_ID, 'default_completion_model_id', Application::DEFAULT_COMPLETION_MODEL_ID) ?: Application::DEFAULT_COMPLETION_MODEL_ID;
		// Max tokens are limited later to max tokens specified in the admin settings so here we just request PHP_INT_MAX
		try {
			$completion = $this->openAiAPIService->createChatCompletion($this->userId, $prompt, 1, $adminModel, PHP_INT_MAX, false);
		} catch (Exception $e) {
			throw new Exception('OpenAI/LocalAI request failed: ' . $e->getMessage());
		}
		if (count($completion) > 0)
			return array_pop($completion);			

		throw new Exception('No result in OpenAI/LocalAI response. ' . ($completion['error'] ?? ''));
	}

	public function getTaskType(): string {
		return FreePromptTaskType::class;
	}
}
