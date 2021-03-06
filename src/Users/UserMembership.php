<?php

namespace Atorscho\Membership\Users;

use Atorscho\Membership\Exceptions\IncorrectParameterType;
use Atorscho\Membership\Groups\Group;
use Atorscho\Membership\Groups\ManageGroups;
use Atorscho\Membership\Permissions\ManagePermissions;
use Atorscho\Membership\Permissions\Permission;
use Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Membership;

trait UserMembership
{
    use ManageGroups, ManagePermissions;

    /**
     * User's groups.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'user_groups');
    }

    /**
     * User's own permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    /**
     * Get all user's group permissions.
     *
     * @return Collection
     */
    public function groupPermissions()
    {
        $permissions = [];
        $groups      = $this->groups;

        foreach ($groups as $group) {
            $groupPermissions = Cache::rememberForever('groups.permissions', function () use ($group) {
                return $group->permissions;
            });

            foreach ($groupPermissions as $permission) {
                $permissions[$permission->id] = $permission;
            }
        }

        return Collection::make($permissions);
    }

    /**
     * Retrieve all user's own permissions and its groups permissions.
     *
     * @return Collection
     */
    public function allPermissions()
    {
        return Cache::rememberForever('permissions.all', function () {
            return $this->permissions->merge($this->groupPermissions());
        });
    }

    /**
     * Check the user belonging to a group.
     *
     * @param array|string $groups Comma or pipe separated list of group handles,
     *                             or an array of handles.
     * @param bool         $strict
     *
     * @return bool
     */
    public function is($groups, $strict = true)
    {
        // Check if $group is a "|" separated list
        if (is_string($groups) && str_contains($groups, '|')) {
            // Strict search is off, check AT LEAST ONE group
            $strict = false;
            $groups = explode('|', $groups);
        } // Or if $group is a "," separated list
        elseif (is_string($groups) && str_contains($groups, ',')) {
            // Strict search is on, check ALL groups
            $strict = true;
            $groups = explode(',', $groups);
        }

        // Ensure $groups is always an array
        $groups = (array) $groups;

        // Ensure group handles are in plural
        $groups = array_map(function ($item) {
            return str_plural($item);
        }, $groups);

        // Check ALL groups
        $userGroups = Cache::rememberForever('groups.handles', function () {
            return $this->groups->lists('handle')->all();
        });

        if ($strict) {
            return count(array_intersect($userGroups, $groups)) == count($groups) && is_logged_in();
        }

        return (bool) array_intersect($userGroups, $groups) && is_logged_in();
    }

    /**
     * Determine if a user has permission to perform some action.
     *
     * @param array|string $permissions Comma or pipe separated list of permission handles,
     *                                  or an array of handles.
     * @param object       $model       [Optional]
     * @param string       $column      [Optional]
     *
     * @return bool
     * @throws IncorrectParameterType
     */
    public function can($permissions, $model = null, $column = 'user_id')
    {
        // Check if user can access the model
        if ($model) {
            if (! $model instanceof Model) {
                throw new IncorrectParameterType('Parameter [$model] must be an instance of the Eloquent Model class.');
            }

            return ($this->can($permissions) || $this->owns($model, $column)) && is_logged_in();
        }

        $strict = true;

        // Check if $permission is a "|" separated list
        if (is_string($permissions) && str_contains($permissions, '|')) {
            // Strict search is off, check AT LEAST ONE permission
            $strict      = false;
            $permissions = explode('|', $permissions);
        } // Or if $permission is a "," separated list
        elseif (is_string($permissions) && str_contains($permissions, ',')) {
            // Strict search is on, check ALL permissions
            $permissions = explode(',', $permissions);
        }

        // Ensure $permission is always an array
        $permissions = (array) $permissions;

        // Check ALL permissions
        if ($strict) {
            return count(array_intersect($this->allPermissions()->lists('handle')->all(),
                $permissions)) == count($permissions) && is_logged_in();
        }

        return (bool) array_intersect($this->allPermissions()->lists('handle')->all(), $permissions) && is_logged_in();
    }

    /**
     * Check user's ownership for a model.
     *
     * @param object $model  [Optional]
     * @param string $column [Optional]
     *
     * @return bool
     * @throws IncorrectParameterType
     */
    public function owns($model, $column = 'user_id')
    {
        if (! $model instanceof Model) {
            throw new IncorrectParameterType('The $model parameter must be an instance of the Eloquent Model.');
        }

        return $model->{$column} == $this->id;
    }

    /**
     * Get user's avatar. If none found, return the default one.
     *
     * @return string
     */
    public function avatar()
    {
        return Membership::avatar($this);
    }

    /**
     * Filter users by their group belonging.
     *
     * @param Builder $query
     * @param string  $group
     *
     * @return mixed
     */
    public function scopeOnly(Builder $query, $group)
    {
        return Group::whereHandle($group)->firstOrFail()->users();
    }
}
