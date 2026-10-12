<?php

namespace App\Support;

final class Labels
{
    public const LEVELS = [
        1 => ['name' => 'Foundation', 'blurb' => 'Understands the basic concepts and vocabulary.'],
        2 => ['name' => 'Applied', 'blurb' => 'Solves standard tasks with limited guidance.'],
        3 => ['name' => 'Professional', 'blurb' => 'Handles typical production work independently.'],
        4 => ['name' => 'Advanced', 'blurb' => 'Owns complex problems, trade-offs and design calls.'],
        5 => ['name' => 'Expert', 'blurb' => 'Deep, demonstrated expertise in a defined specialty.'],
    ];

    public const PROFILE_STATUS = [
        'draft' => 'Draft',
        'under_review' => 'Under review',
        'awaiting_assessment' => 'Awaiting assessment',
        'conditionally_approved' => 'Conditionally approved',
        'approved' => 'Approved',
        'suspended' => 'Suspended',
    ];

    public const AVAILABILITY = [
        'open_to_work' => 'Open to work',
        'contract' => 'Available for contract',
        'freelance' => 'Freelance only',
        'unavailable' => 'Not available right now',
    ];

    public const ARRANGEMENTS = [
        'remote' => 'Remote',
        'hybrid' => 'Hybrid',
        'onsite' => 'On-site',
        'any' => 'Open to anything',
    ];

    public const EMPLOYMENT_TYPES = [
        'full_time' => 'Full-time',
        'part_time' => 'Part-time',
        'contract' => 'Contract',
        'temporary' => 'Temporary',
        'internship' => 'Internship',
    ];

    public const SENIORITY = [
        'junior' => 'Junior',
        'mid' => 'Mid-level',
        'senior' => 'Senior',
        'lead' => 'Lead / Principal',
    ];

    public const ATTEMPT_STATUS = [
        'in_progress' => 'In progress',
        'submitted' => 'Submitted',
        'under_review' => 'Under review',
        'passed' => 'Passed',
        'not_passed' => 'Not yet passed',
        'voided' => 'Voided',
    ];

    public const CONTRACT_STATUS = [
        'pending_acceptance' => 'Awaiting acceptance',
        'active' => 'Active',
        'completed' => 'Completed',
        'disputed' => 'In dispute',
        'cancelled' => 'Cancelled',
    ];

    public const MILESTONE_STATUS = [
        'pending' => 'Not funded',
        'funding_pending' => 'Payment processing',
        'funded' => 'Funded · in progress',
        'submitted' => 'Submitted for review',
        'revision_requested' => 'Revision requested',
        'accepted' => 'Accepted',
        'released' => 'Paid out',
        'disputed' => 'On hold · dispute',
        'refunded' => 'Refunded',
    ];

    public const CURRENCIES = ['NGN' => 'Nigerian naira (₦)', 'USD' => 'US dollar ($)'];

    public static function tone(string $status): string
    {
        return match ($status) {
            'approved', 'passed', 'active', 'released', 'accepted', 'hired', 'succeeded', 'paid', 'open', 'published', 'completed', 'resolved' => 'green',
            'under_review', 'submitted', 'funding_pending', 'in_progress', 'interview', 'offer', 'pending', 'requested', 'pending_acceptance', 'conditionally_approved', 'awaiting_assessment', 'shortlisted', 'contacted' => 'amber',
            'not_passed', 'suspended', 'disputed', 'failed', 'rejected', 'cancelled', 'refunded', 'voided', 'closed' => 'red',
            'funded', 'revision_requested', 'new' => 'violet',
            default => 'gray',
        };
    }
}
