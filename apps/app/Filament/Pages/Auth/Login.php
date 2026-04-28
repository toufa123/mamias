<?php
    
    namespace App\Filament\Pages\Auth;
    
    use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
    use Filament\Auth\Pages\Login as BaseLogin;
    
    class Login extends BaseLogin
    {
        use HasCustomLayout;
        
        public function mount(): void
        {
            parent::mount();
            
            if (auth()->check()) {
                $this->redirect($this->getRedirectUrl());
            }
        }
        
        public function getRedirectUrl(): string
        {
            $user = auth()->user();
            
            if ($user && ($user->hasRole('super_admin') || $user->hasRole('panel_user'))) {
                return filament()->getPanel('mamias')->getUrl();
            }
            
            // This is important: users with only 'user' role should be redirected to home
            return url('/');
        }
    }
