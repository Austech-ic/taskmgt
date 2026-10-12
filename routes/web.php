<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\App as Dash;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Site;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Installer (only reachable until storage/app/installed.lock exists)
|--------------------------------------------------------------------------
*/
Route::prefix('install')->name('install.')->controller(InstallController::class)->group(function () {
    Route::get('/', 'welcome')->name('welcome');
    Route::get('/database', 'database')->name('database');
    Route::post('/database', 'saveDatabase')->name('database.save');
    Route::get('/site', 'site')->name('site');
    Route::post('/site', 'finish')->name('finish');
});

Route::get('/media/{path}', MediaController::class)->where('path', '.*')->name('media');

/*
|--------------------------------------------------------------------------
| Public site + CMS
|--------------------------------------------------------------------------
*/
Route::get('/', [Site\HomeController::class, 'index'])->name('home');
Route::get('/talent', [Site\TalentController::class, 'index'])->name('talent.index');
Route::post('/talent/ai', [Site\TalentController::class, 'aiSearch'])->middleware('throttle:20,1')->name('talent.ai');
Route::get('/u/{profile}', [Site\TalentController::class, 'show'])->name('talent.show');
Route::get('/jobs', [Site\JobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [Site\JobController::class, 'show'])->name('jobs.show');
Route::get('/projects', [Site\ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project}', [Site\ProjectController::class, 'show'])->name('projects.show');
Route::get('/skills', [Site\SkillController::class, 'index'])->name('skills.index');
Route::get('/pricing', [Site\PageController::class, 'pricing'])->name('pricing');
Route::get('/how-it-works', [Site\PageController::class, 'how'])->name('how');
Route::get('/faq', [Site\PageController::class, 'faq'])->name('faq');
Route::get('/contact', [Site\PageController::class, 'contact'])->name('contact');
Route::post('/contact', [Site\PageController::class, 'sendContact'])->middleware('throttle:5,1')->name('contact.send');
Route::get('/blog', [Site\BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post}', [Site\BlogController::class, 'show'])->name('blog.show');
Route::get('/p/{page}', [Site\PageController::class, 'show'])->name('pages.show');
Route::post('/assistant', [Site\AssistantController::class, 'ask'])->middleware('throttle:15,1')->name('assistant');
Route::get('/sitemap.xml', [Site\PageController::class, 'sitemap'])->name('sitemap');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/join', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/join', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [PasswordController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'update'])->name('password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/verify-email', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/verify-email/resend', [VerificationController::class, 'resend'])->middleware('throttle:3,1')->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Payments
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/pay/{transaction}', [PaymentController::class, 'checkout'])->name('payments.checkout');
    Route::get('/pay/{transaction}/callback', [PaymentController::class, 'callback'])->name('payments.callback');
    Route::get('/pay/{transaction}/sandbox', [PaymentController::class, 'sandbox'])->name('payments.sandbox');
    Route::post('/pay/{transaction}/sandbox', [PaymentController::class, 'sandboxComplete'])->name('payments.sandbox.complete');
});
Route::post('/webhooks/paystack', [PaymentController::class, 'paystackWebhook'])->name('webhooks.paystack');

/*
|--------------------------------------------------------------------------
| Member app
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'verified.if'])->prefix('app')->name('app.')->group(function () {
    Route::get('/', [Dash\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/onboarding', [Dash\DashboardController::class, 'onboarding'])->name('onboarding');
    Route::post('/onboarding', [Dash\DashboardController::class, 'saveOnboarding'])->name('onboarding.save');
    Route::post('/workspace/{workspace}', [Dash\DashboardController::class, 'switch'])->name('workspace');

    // Shared
    Route::get('/messages', [Dash\MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{conversation}', [Dash\MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}', [Dash\MessageController::class, 'send'])->middleware('throttle:30,1')->name('messages.send');
    Route::get('/messages/{conversation}/attachment/{message}', [Dash\MessageController::class, 'attachment'])->name('messages.attachment');
    Route::get('/notifications', [Dash\NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/read', [Dash\NotificationController::class, 'readAll'])->name('notifications.read');
    Route::get('/notifications/{id}', [Dash\NotificationController::class, 'open'])->name('notifications.open');
    Route::get('/settings', [Dash\SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [Dash\SettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/password', [Dash\SettingsController::class, 'password'])->name('settings.password');
    Route::get('/contracts', [Dash\ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/{contract}', [Dash\ContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts/{contract}/accept', [Dash\ContractController::class, 'accept'])->name('contracts.accept');
    Route::post('/contracts/{contract}/milestones/{milestone}/fund', [Dash\ContractController::class, 'fund'])->name('contracts.fund');
    Route::post('/contracts/{contract}/milestones/{milestone}/deliver', [Dash\ContractController::class, 'deliver'])->name('contracts.deliver');
    Route::post('/contracts/{contract}/milestones/{milestone}/approve', [Dash\ContractController::class, 'approve'])->name('contracts.approve');
    Route::post('/contracts/{contract}/milestones/{milestone}/revise', [Dash\ContractController::class, 'revise'])->name('contracts.revise');
    Route::post('/contracts/{contract}/dispute', [Dash\ContractController::class, 'dispute'])->name('contracts.dispute');
    Route::post('/contracts/{contract}/review', [Dash\ContractController::class, 'review'])->name('contracts.review');
    Route::get('/contracts/{contract}/deliverables/{deliverable}', [Dash\ContractController::class, 'download'])->name('contracts.download');
    Route::post('/contacts/{profile}/message', [Dash\MessageController::class, 'start'])->middleware('throttle:10,1')->name('messages.start');

    // AI helpers (JSON)
    Route::post('/ai/{feature}', [Dash\AiController::class, 'run'])->middleware('throttle:20,1')->name('ai');

    // Talent workspace
    Route::middleware('workspace:talent')->group(function () {
        Route::get('/profile', [Dash\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [Dash\ProfileController::class, 'update'])->name('profile.update');
        Route::get('/profile/skills', [Dash\ProfileController::class, 'skills'])->name('profile.skills');
        Route::post('/profile/skills', [Dash\ProfileController::class, 'addSkill'])->name('profile.skills.add');
        Route::put('/profile/skills/{profileSkill}', [Dash\ProfileController::class, 'updateSkill'])->name('profile.skills.update');
        Route::delete('/profile/skills/{profileSkill}', [Dash\ProfileController::class, 'removeSkill'])->name('profile.skills.remove');
        Route::get('/profile/experience', [Dash\ProfileController::class, 'experience'])->name('profile.experience');
        Route::post('/profile/experience', [Dash\ProfileController::class, 'saveExperience'])->name('profile.experience.save');
        Route::delete('/profile/experience/{experience}', [Dash\ProfileController::class, 'deleteExperience'])->name('profile.experience.delete');
        Route::post('/profile/certifications', [Dash\ProfileController::class, 'saveCertification'])->name('profile.certifications.save');
        Route::delete('/profile/certifications/{certification}', [Dash\ProfileController::class, 'deleteCertification'])->name('profile.certifications.delete');
        Route::get('/profile/portfolio', [Dash\ProfileController::class, 'portfolio'])->name('profile.portfolio');
        Route::post('/profile/portfolio', [Dash\ProfileController::class, 'savePortfolio'])->name('profile.portfolio.save');
        Route::delete('/profile/portfolio/{item}', [Dash\ProfileController::class, 'deletePortfolio'])->name('profile.portfolio.delete');
        Route::post('/profile/submit', [Dash\ProfileController::class, 'submit'])->name('profile.submit');
        Route::get('/profile/cv', [Dash\ProfileController::class, 'cv'])->name('profile.cv');

        Route::get('/assessments', [Dash\AssessmentController::class, 'index'])->name('assessments.index');
        Route::get('/assessments/{assessment}', [Dash\AssessmentController::class, 'show'])->name('assessments.show');
        Route::post('/assessments/{assessment}/start', [Dash\AssessmentController::class, 'start'])->name('assessments.start');
        Route::get('/attempts/{attempt}', [Dash\AssessmentController::class, 'take'])->name('attempts.take');
        Route::post('/attempts/{attempt}/save', [Dash\AssessmentController::class, 'save'])->name('attempts.save');
        Route::post('/attempts/{attempt}/submit', [Dash\AssessmentController::class, 'submit'])->name('attempts.submit');
        Route::get('/attempts/{attempt}/result', [Dash\AssessmentController::class, 'result'])->name('attempts.result');

        Route::get('/applications', [Dash\TalentWorkController::class, 'applications'])->name('applications.index');
        Route::post('/jobs/{job}/apply', [Dash\TalentWorkController::class, 'apply'])->name('jobs.apply');
        Route::post('/applications/{application}/withdraw', [Dash\TalentWorkController::class, 'withdraw'])->name('applications.withdraw');
        Route::get('/proposals', [Dash\TalentWorkController::class, 'proposals'])->name('proposals.index');
        Route::post('/projects/{project}/propose', [Dash\TalentWorkController::class, 'propose'])->name('projects.propose');
        Route::post('/proposals/{proposal}/withdraw', [Dash\TalentWorkController::class, 'withdrawProposal'])->name('proposals.withdraw');
        Route::get('/requests', [Dash\TalentWorkController::class, 'requests'])->name('requests.index');
        Route::post('/requests/{contactRequest}/{decision}', [Dash\TalentWorkController::class, 'respond'])->whereIn('decision', ['accept', 'decline'])->name('requests.respond');
        Route::get('/earnings', [Dash\EarningsController::class, 'index'])->name('earnings');
        Route::post('/earnings/payout', [Dash\EarningsController::class, 'requestPayout'])->name('earnings.payout');
    });

    // Hirer workspace (employers + clients)
    Route::middleware('workspace:hirer')->group(function () {
        Route::get('/company', [Dash\OrganizationController::class, 'edit'])->name('company.edit');
        Route::put('/company', [Dash\OrganizationController::class, 'update'])->name('company.update');
        Route::post('/company/members', [Dash\OrganizationController::class, 'addMember'])->name('company.members.add');
        Route::delete('/company/members/{user}', [Dash\OrganizationController::class, 'removeMember'])->name('company.members.remove');

        Route::middleware('has.org')->group(function () {
            Route::post('/talent/{profile}/unlock', [Dash\RecruitController::class, 'unlock'])->name('talent.unlock');
            Route::get('/talent/{profile}/cv', [Dash\RecruitController::class, 'cv'])->name('talent.cv');
            Route::post('/talent/{profile}/request', [Dash\RecruitController::class, 'contactRequest'])->name('talent.request');
            Route::post('/talent/{profile}/shortlist', [Dash\RecruitController::class, 'addToShortlist'])->name('talent.shortlist');
            Route::get('/shortlists', [Dash\RecruitController::class, 'shortlists'])->name('shortlists.index');
            Route::post('/shortlists', [Dash\RecruitController::class, 'createShortlist'])->name('shortlists.store');
            Route::get('/shortlists/{shortlist}', [Dash\RecruitController::class, 'shortlist'])->name('shortlists.show');
            Route::get('/shortlists/{shortlist}/export', [Dash\RecruitController::class, 'export'])->name('shortlists.export');
            Route::put('/shortlists/{shortlist}/items/{item}', [Dash\RecruitController::class, 'updateItem'])->name('shortlists.items.update');
            Route::delete('/shortlists/{shortlist}/items/{item}', [Dash\RecruitController::class, 'removeItem'])->name('shortlists.items.remove');
            Route::delete('/shortlists/{shortlist}', [Dash\RecruitController::class, 'deleteShortlist'])->name('shortlists.destroy');
            Route::get('/unlocks', [Dash\RecruitController::class, 'unlocks'])->name('unlocks.index');
            Route::post('/saved-searches', [Dash\RecruitController::class, 'saveSearch'])->name('searches.store');
            Route::delete('/saved-searches/{search}', [Dash\RecruitController::class, 'deleteSearch'])->name('searches.destroy');
            Route::get('/compare', [Dash\RecruitController::class, 'compare'])->name('compare');

            Route::get('/billing', [Dash\BillingController::class, 'index'])->name('billing');
            Route::post('/billing/subscribe/{plan}', [Dash\BillingController::class, 'subscribe'])->name('billing.subscribe');
            Route::post('/billing/credits', [Dash\BillingController::class, 'credits'])->name('billing.credits');

            Route::get('/jobs', [Dash\JobController::class, 'index'])->name('jobs.index');
            Route::get('/jobs/create', [Dash\JobController::class, 'create'])->name('jobs.create');
            Route::post('/jobs', [Dash\JobController::class, 'store'])->name('jobs.store');
            Route::get('/jobs/{job}/edit', [Dash\JobController::class, 'edit'])->name('jobs.edit');
            Route::put('/jobs/{job}', [Dash\JobController::class, 'update'])->name('jobs.update');
            Route::get('/jobs/{job}/pipeline', [Dash\JobController::class, 'pipeline'])->name('jobs.pipeline');
            Route::put('/applications/{application}/stage', [Dash\JobController::class, 'moveStage'])->name('applications.stage');
            Route::get('/applications/{application}/cv', [Dash\JobController::class, 'cv'])->name('applications.cv');
        });

        Route::get('/projects', [Dash\ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/create', [Dash\ProjectController::class, 'create'])->name('projects.create');
        Route::post('/projects', [Dash\ProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}/edit', [Dash\ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('/projects/{project}', [Dash\ProjectController::class, 'update'])->name('projects.update');
        Route::get('/projects/{project}/proposals', [Dash\ProjectController::class, 'proposals'])->name('projects.proposals');
        Route::get('/projects/{project}/proposals/{proposal}/hire', [Dash\ProjectController::class, 'hireForm'])->name('projects.hire');
        Route::post('/projects/{project}/proposals/{proposal}/hire', [Dash\ProjectController::class, 'hire'])->name('projects.hire.store');
        Route::post('/projects/{project}/proposals/{proposal}/shortlist', [Dash\ProjectController::class, 'shortlistProposal'])->name('projects.proposals.shortlist');
    });
});

/*
|--------------------------------------------------------------------------
| Admin console
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('staff:reviewer,admin')->group(function () {
        Route::get('/reviews/attempts', [Admin\AssessmentReviewController::class, 'index'])->name('attempts.index');
        Route::get('/reviews/attempts/{attempt}', [Admin\AssessmentReviewController::class, 'show'])->name('attempts.show');
        Route::post('/reviews/attempts/{attempt}', [Admin\AssessmentReviewController::class, 'decide'])->name('attempts.decide');
        Route::get('/assessments', [Admin\AssessmentController::class, 'index'])->name('assessments.index');
        Route::get('/assessments/create', [Admin\AssessmentController::class, 'create'])->name('assessments.create');
        Route::post('/assessments', [Admin\AssessmentController::class, 'store'])->name('assessments.store');
        Route::get('/assessments/{assessment}/edit', [Admin\AssessmentController::class, 'edit'])->name('assessments.edit');
        Route::put('/assessments/{assessment}', [Admin\AssessmentController::class, 'update'])->name('assessments.update');
        Route::post('/assessments/{assessment}/questions', [Admin\AssessmentController::class, 'storeQuestion'])->name('questions.store');
        Route::put('/questions/{question}', [Admin\AssessmentController::class, 'updateQuestion'])->name('questions.update');
        Route::delete('/questions/{question}', [Admin\AssessmentController::class, 'deleteQuestion'])->name('questions.destroy');
        Route::post('/assessments/{assessment}/ai-draft', [Admin\AssessmentController::class, 'aiDraft'])->name('assessments.ai');
    });

    Route::middleware('staff:admin')->group(function () {
        Route::get('/talent', [Admin\TalentController::class, 'index'])->name('talent.index');
        Route::get('/talent/{profile}', [Admin\TalentController::class, 'show'])->name('talent.show');
        Route::post('/talent/{profile}/decide', [Admin\TalentController::class, 'decide'])->name('talent.decide');
        Route::post('/talent/{profile}/verify', [Admin\TalentController::class, 'verify'])->name('talent.verify');
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
        Route::get('/organizations', [Admin\OrganizationController::class, 'index'])->name('organizations.index');
        Route::post('/organizations/{organization}/verify', [Admin\OrganizationController::class, 'verify'])->name('organizations.verify');
        Route::post('/organizations/{organization}/credits', [Admin\OrganizationController::class, 'credits'])->name('organizations.credits');
        Route::get('/listings', [Admin\ListingController::class, 'index'])->name('listings.index');
        Route::post('/listings/{type}/{id}/status', [Admin\ListingController::class, 'status'])->name('listings.status');
        Route::get('/reviews', [Admin\ListingController::class, 'reviews'])->name('reviews.index');
        Route::post('/reviews/{review}', [Admin\ListingController::class, 'moderateReview'])->name('reviews.moderate');
        Route::get('/messages', [Admin\ContentController::class, 'inbox'])->name('inbox');
        Route::post('/messages/{message}', [Admin\ContentController::class, 'inboxStatus'])->name('inbox.status');
        Route::get('/settings/{group?}', [Admin\SettingsController::class, 'edit'])->name('settings');
        Route::put('/settings/{group}', [Admin\SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/ai/test', [Admin\SettingsController::class, 'testAi'])->name('settings.ai.test');
        Route::get('/audit', [Admin\DashboardController::class, 'audit'])->name('audit');
        Route::post('/ai/blog', [Admin\ContentController::class, 'aiBlog'])->name('ai.blog');

        // Generic CMS / catalogue resources
        Route::get('/r/{resource}', [Admin\ResourceController::class, 'index'])->name('resources.index');
        Route::get('/r/{resource}/create', [Admin\ResourceController::class, 'create'])->name('resources.create');
        Route::post('/r/{resource}', [Admin\ResourceController::class, 'store'])->name('resources.store');
        Route::get('/r/{resource}/{id}/edit', [Admin\ResourceController::class, 'edit'])->name('resources.edit');
        Route::put('/r/{resource}/{id}', [Admin\ResourceController::class, 'update'])->name('resources.update');
        Route::delete('/r/{resource}/{id}', [Admin\ResourceController::class, 'destroy'])->name('resources.destroy');
    });

    Route::middleware('staff:finance,admin')->group(function () {
        Route::get('/finance', [Admin\FinanceController::class, 'index'])->name('finance.index');
        Route::get('/finance/transactions', [Admin\FinanceController::class, 'transactions'])->name('finance.transactions');
        Route::post('/finance/transactions/{transaction}/recheck', [Admin\FinanceController::class, 'recheck'])->name('finance.recheck');
        Route::get('/finance/ledger', [Admin\FinanceController::class, 'ledger'])->name('finance.ledger');
        Route::get('/finance/payouts', [Admin\FinanceController::class, 'payouts'])->name('finance.payouts');
        Route::post('/finance/payouts/{payout}', [Admin\FinanceController::class, 'payoutAction'])->name('finance.payouts.action');
        Route::get('/disputes', [Admin\DisputeController::class, 'index'])->name('disputes.index');
        Route::get('/disputes/{dispute}', [Admin\DisputeController::class, 'show'])->name('disputes.show');
        Route::post('/disputes/{dispute}', [Admin\DisputeController::class, 'resolve'])->name('disputes.resolve');
        Route::get('/contracts', [Admin\DisputeController::class, 'contracts'])->name('contracts.index');
    });
});
