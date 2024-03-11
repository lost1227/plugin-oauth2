<?php

namespace Kanboard\Plugin\OAuth2\Helper;

use Kanboard\Core\User\UserProviderInterface;
use Kanboard\Plugin\OAuth2\User\GenericOAuth2UserProvider;
use Kanboard\Core\Base;

class OAuth2AvatarHelper extends Base
{
    public function synchronizeAvatar(UserProviderInterface $user)
    {
        if(! $user instanceof GenericOAuth2UserProvider) {
            $this->logger->debug('OAuth2: Ignoring synchronization event because $user is not an instance of GenericOAuth2UserProvider');
            return;
        }

        if(! $this->userSession->isLogged()) {
            $this->logger->debug('OAuth2: Ignoring synchronization event because no user is logged in');
            return;
        }

        $user_id = $this->userSession->getId();
        $avatar_url = $user->getAvatarUrl();

        $user_model = $this->userModel->getById($user_id);
        $external_id_column = $user->getExternalIdColumn();
        if(!isset($user_model[$external_id_column]) || $user_model[$external_id_column] != $user->getExternalId()) {
            $this->logger->debug('OAuth2: Ignoring synchronization event because the provided $user is not the currently logged-in user.');
            return;
        }

        $this->logger->debug('OAuth2: Synchronizing avatar for '.$user->getUsername().' ('.$user_id.')');

        if(empty($avatar_url)) {
            $this->logger->debug('OAuth2: '.$user->getUsername().' has no avatar.');
            return;
        }

        if(!$this->avatarFileModel->isAvatarImage($avatar_url)) {
            $this->logger->debug('OAuth2: Invalid avatar file type: ' + $avatar_url);
            return;
        }

        $this->logger->debug('OAuth2: Downloading user avatar from: ' . $avatar_url);

        $data = $this->httpClient->get($avatar_url);
        if(empty($data)) {
            $this->logger->debug('OAuth2: User avatar download failed!');
            return;
        }

        $this->avatarFileModel->uploadImageContent($user_id, $data);
    }
}
