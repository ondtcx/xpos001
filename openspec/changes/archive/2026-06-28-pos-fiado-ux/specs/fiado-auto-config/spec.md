# Fiado Auto-Activation Configuration Specification

## Purpose

Allow users to configure whether fiado (store credit) auto-activates when received amount is less than total. Default behavior is auto-activation enabled.

## Requirements

### Requirement: Configurable Auto-Activation

The system MUST provide a user setting to enable or disable fiado auto-activation. The default value MUST be enabled (auto-activation on).

#### Scenario: Auto-activation enabled (default)

- GIVEN fiado auto-activation is enabled
- AND the user enters a received amount less than the total
- WHEN the user confirms the payment
- THEN the system MUST automatically activate fiado for the remaining balance
- AND the system MUST prompt for client selection if no client is selected

#### Scenario: Auto-activation disabled

- GIVEN fiado auto-activation is disabled
- AND the user enters a received amount less than the total
- WHEN the user confirms the payment
- THEN the system MUST NOT automatically activate fiado
- AND the system MUST show a validation error indicating the amount is insufficient

#### Scenario: User toggles auto-activation setting

- GIVEN the user is in app settings
- WHEN the user toggles the fiado auto-activation setting
- THEN the new value MUST be persisted
- AND subsequent POS sessions MUST use the updated setting

### Requirement: Setting Persistence

The system MUST persist the fiado auto-activation setting per user. The setting MUST survive session restarts.

#### Scenario: Setting persists across sessions

- GIVEN the user disabled fiado auto-activation
- WHEN the user logs out and logs back in
- THEN the fiado auto-activation setting MUST remain disabled
