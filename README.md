# PageVersions

PageVersions is a MediaWiki extension that adds semantic page versions on top of normal revision history. It lets editors create `major`, `minor`, and `patch` releases for a page, view those versions in the UI, and consume them from integrations such as workflows and automations.

## Features

- Create semantic versions for a page revision
- Show version information in enhanced page history views
- Expose the current page version through parser variables
- Provide REST endpoints for creating, deleting, and previewing the next version
- Integrate with PageCheckout, Workflows, WikiAutomations, and BlueSpice Discovery when those extensions are present

## Requirements

- MediaWiki `>= 1.43.0`
- PHP with Composer support

## Installation

Clone the extension into your MediaWiki `extensions/` directory:

```php
wfLoadExtension( 'PageVersions' );
```

Then run the normal MediaWiki maintenance flow for schema updates.

## Configuration

The extension exposes one configuration setting:

```php
$wgPageVersionsLevels = [ 'major', 'minor', 'patch' ];
```

This controls which version levels are available to users. By default, all three are enabled.

## Usage

### Creating versions

Once the extension is enabled, users with edit permission can create page versions through the extension UI and supported integrations.

### Parser variables

Use the built-in variable on the current page:

```wikitext
{{PAGEVERSION}}
```

Use the parser function for a specific page:

```wikitext
{{#PAGEVERSION:PageName}}
```

If the target page does not exist or has no version yet, the result is an empty string.

### REST API

The extension registers the following REST routes:

- `POST /page_versions/create`
- `POST /page_versions/delete`
- `GET /page_versions/next/{pageId}`

The `next` endpoint returns the current version and the next available semantic bumps based on the configured levels.

## Integrations

PageVersions includes integration points for several related extensions and platforms:

- EnhancedStandardUIs history page plugin
- BlueSpice Discovery metadata provider
- WikiAutomations trigger: `page_version_created`
- PageCheckout plugin support
- Workflows activity: `new_page_version`

Example workflow task:

```xml
<bpmn:task id="NewPageVersion" name="new-page-version">
    <bpmn:extensionElements>
        <wf:type>new_page_version</wf:type>
    </bpmn:extensionElements>
    <bpmn:property name="version_type">major</bpmn:property>
</bpmn:task>
```

If `pagename` is omitted, the workflow context page is used.

## Extension hooks and events

PageVersions registers MediaWiki hooks for schema updates, parser variables, navigation actions, read confirmation integration, and request handling around version resolution.

It also emits these extension-level events:

- `PageVersionCreated`
- `PageVersionDeleted`
