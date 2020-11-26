# Asset Audit Plugin for GLPI
Audit the physical state of one asset or a bunch of assets and update the last physical inventory date.
You can plan audits in advance and track failures (directly) and remediations (through tickets).

Current / Planned Workflows:
- [ ] Quick Audits (Impromptu): Search for an asset, review some fields, then either complete the audit with a success or by opening a ticket to start a remediation.
- [ ] Planned Audits: Prepare an audit by location, entity, or with a group of specific assets.
Allow you to go through a list of assets needing audited, review their information, and either pass or fail the audit.
If failed, you can open a ticket for the asset and upon solving, the audit will be marked as remediated.