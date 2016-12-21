<?php

namespace Pim\Bundle\EnrichBundle\Controller\Rest;

use Akeneo\Component\Batch\Job\JobParametersFactory;
use Akeneo\Component\Batch\Job\JobParametersValidator;
use Akeneo\Component\Batch\Job\JobRegistry;
use Akeneo\Component\Batch\Model\JobInstance;
use Akeneo\Component\StorageUtils\Remover\RemoverInterface;
use Akeneo\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use Akeneo\Component\StorageUtils\Saver\SaverInterface;
use Akeneo\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ValidatorInterface;

/**
 * JobInstance rest controller
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class JobInstanceController
{
    /** @var IdentifiableObjectRepositoryInterface */
    protected $repository;

    /** @var JobRegistry */
    protected $jobRegistry;

    /** @var NormalizerInterface */
    protected $jobInstanceNormalizer;

    /** @var NormalizerInterface */
    protected $normalizer;

    /** @var ObjectUpdaterInterface */
    protected $updater;

    /** @var SaverInterface */
    protected $saver;

    /** @var RemoverInterface */
    protected $remover;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var JobParametersValidator */
    protected $jobParameterValidator;

    /** @var JobParametersFactory */
    protected $jobParamsFactory;

    /**
     * @param IdentifiableObjectRepositoryInterface $repository
     * @param JobRegistry                           $jobRegistry
     * @param NormalizerInterface                   $jobInstanceNormalizer
     * @param NormalizerInterface                   $normalizer
     * @param ObjectUpdaterInterface                $updater
     * @param SaverInterface                        $saver
     * @param RemoverInterface                      $remover
     * @param ValidatorInterface                    $validator
     * @param JobParametersValidator                $jobParameterValidator
     * @param JobParametersFactory                  $jobParamsFactory
     */
    public function __construct(
        IdentifiableObjectRepositoryInterface $repository,
        JobRegistry $jobRegistry,
        NormalizerInterface $jobInstanceNormalizer,
        NormalizerInterface $normalizer,
        ObjectUpdaterInterface $updater,
        SaverInterface $saver,
        RemoverInterface $remover,
        ValidatorInterface $validator,
        JobParametersValidator $jobParameterValidator,
        JobParametersFactory $jobParamsFactory
    ) {
        $this->repository            = $repository;
        $this->jobRegistry           = $jobRegistry;
        $this->jobInstanceNormalizer = $jobInstanceNormalizer;
        $this->normalizer            = $normalizer;
        $this->updater               = $updater;
        $this->saver                 = $saver;
        $this->remover               = $remover;
        $this->validator             = $validator;
        $this->jobParameterValidator = $jobParameterValidator;
        $this->jobParamsFactory      = $jobParamsFactory;
    }

    /**
     * Get a job profile
     *
     * @param string $identifier
     *
     * @AclAncestor("pim_importexport_export_profile_show")
     *
     * @return JsonResponse
     */
    public function getAction($identifier)
    {
        $jobInstance = $this->getJobInstance($identifier);

        return new JsonResponse($this->normalizeJobInstance($jobInstance));
    }

    /**
     * Edit a job profile
     *
     * @param Request $request
     * @param string  $identifier
     *
     * @AclAncestor("pim_importexport_export_profile_edit")
     *
     * @return JsonResponse
     */
    public function putAction(Request $request, $identifier)
    {
        $jobInstance = $this->getJobInstance($identifier);
        $data = json_decode($request->getContent(), true);
        $this->updater->update($jobInstance, $data);

        $errors = $this->getValidationErrors($jobInstance);
        if (count($errors) > 0) {
            return new JsonResponse($errors, 400);
        }

        $this->saver->save($jobInstance);

        return new JsonResponse($this->normalizeJobInstance($jobInstance));
    }

    /**
     * Delete a job profile
     *
     * @param string $code
     *
     * @AclAncestor("pim_importexport_export_profile_remove")
     *
     * @return JsonResponse
     */
    public function deleteAction($code)
    {
        $jobInstance = $this->getJobInstance($code);

        $this->remover->remove($jobInstance);

        return new JsonResponse();
    }

    /**
     * Get a job instance
     *
     * @param int  $id
     * @param bool $checkStatus
     *
     * @throws NotFoundHttpException
     *
     * @return JobInstance|RedirectResponse
     */
    protected function getJobInstance($id, $checkStatus = true)
    {
        $jobInstance = $this->repository->findOneByIdentifier($id);

        if (null === $jobInstance) {
            throw new NotFoundHttpException('Akeneo\Component\Batch\Model\JobInstance entity not found');
        }

        // Fixme: should look at the job execution to see the status of a job instance execution
        if ($checkStatus && $jobInstance->getStatus() === JobInstance::STATUS_IN_PROGRESS) {
            throw new NotFoundHttpException(
                sprintf('The %s "%s" is currently in progress', $jobInstance->getType(), $jobInstance->getLabel())
            );
        }

        $job = $this->jobRegistry->get($jobInstance->getJobName());

        if (!$job) {
            throw new NotFoundHttpException(
                sprintf(
                    'The following %s does not exist anymore. Please check configuration:<br />' .
                    'Connector: %s<br />' .
                    'Type: %s<br />' .
                    'Alias: %s',
                    $jobInstance->getType(),
                    $jobInstance->getConnector(),
                    $jobInstance->getType(),
                    $jobInstance->getJobName()
                )
            );
        }

        return $jobInstance;
    }

    protected function getValidationErrors(JobInstance $jobInstance)
    {
        $rawParameters = $jobInstance->getRawParameters();
        if (!empty($rawParameters)) {
            $job = $this->jobRegistry->get($jobInstance->getJobName());
            $parameters = $this->jobParamsFactory->create($job, $rawParameters);
            $parametersViolations = $this->jobParameterValidator->validate($job, $parameters);
        }

        $errors = [];
        $accessor = PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();
        if ($parametersViolations->count() > 0) {
            foreach ($parametersViolations as $error) {
                $accessor->setValue($errors, '[configuration]' . $error->getPropertyPath(), $error->getMessage());
            }
        }

        $globalViolations = $this->validator->validate($jobInstance);
        if ($globalViolations->count() > 0) {
            foreach ($globalViolations as $error) {
                $errors[$error->getPropertyPath()] = $error->getMessage();
            }
        }

        return $errors;
    }

    protected function normalizeJobInstance(JobInstance $jobInstance)
    {
        $normalizedJobInstance = $this->jobInstanceNormalizer->normalize($jobInstance, 'standard');

        return array_merge($normalizedJobInstance, [
            'meta' => [
                'form' => sprintf('pim-job-instance-%s-edit-form', str_replace('_', '-', $jobInstance->getJobName())),
                'id'   => $jobInstance->getId()
            ]
        ]);
    }
}