<?php

namespace Clearlyip\LaravelFlipt\Enums;

enum ScmType: string
{
    case SCM_UNKNOWN = 'SCM_UNKNOWN';
    case SCM_GITHUB = 'SCM_GITHUB';
    case SCM_GITEA = 'SCM_GITEA';
    case SCM_GITLAB = 'SCM_GITLAB';
    case SCM_AZURE = 'SCM_AZURE';
    case SCM_BITBUCKET = 'SCM_BITBUCKET';
}
