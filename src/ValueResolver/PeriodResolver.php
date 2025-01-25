<?php

declare(strict_types=1);

namespace App\ValueResolver;

use App\Model\Period;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class PeriodResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== Period::class) {
            return [];
        }

        $initialFrom = "1970-01-01T00:00:00.000Z";
        $initialTo = "now";

        $from = $request->query->get('from', $initialFrom);
        $to = $request->query->get('to', $initialTo);

        try {
            $from = new \DateTimeImmutable($from);
        } catch (\Exception) {
            $from = new DateTimeImmutable($initialFrom);
        }

        try {
            $to = new \DateTimeImmutable($to);
        } catch (\Exception) {
            $to = new DateTimeImmutable($initialTo);
        }

        $period = new Period($from, $to);

        return [$period];
    }
}
