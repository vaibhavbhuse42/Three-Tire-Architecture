# Three-Tire-Architecture

# 1) Overview

This document explains a Three-Tier Architecture (Web → App → DB) on AWS. Each component (VPC, Subnets, Route Tables, Internet Gateway, EC2, RDS, S3, CloudFront, IAM/Access Keys) is explained in detail. This file can be directly used as a GitHub README.

# 2) Architecture Diagram

![](/images/Gemini_Generated_Image_oeoh48oeoh48oeoh.png)

# 3) VPC (Virtual Private Cloud)

![](/images/Screenshot%202025-09-17%20164711.png)

Purpose: Isolated network for all project resources.

Recommended CIDR: 10.0.0.0/16.

Configuration:

Enable DNS hostnames.

Example Tag: Name=three-tier-vpc.

# 4) Subnets (4 Subnets Total)

![](/images/Screenshot%202025-09-17%20164726.png)

Design:

Web Subnet (Public) — 10.0.1.0/24

Public IP enabled.

Hosts Web EC2 or Load Balancer.

App Subnet (Private) — 10.0.2.0/24

Private EC2 (no public IP).

Hosts App server.

DB Subnet A (Private) — 10.0.3.0/24 (AZ-A).

DB Subnet B (Private) — 10.0.4.0/24 (AZ-B).

Used by RDS Multi-AZ for high availability.

# 5) Route Tables

![](/images/Screenshot%202025-09-17%20164742.png)

Public Route Table:

Attached to Web Subnet.

Route 0.0.0.0/0 → Internet Gateway.

Private Route Table:

Attached to App Subnet.

Optional NAT Gateway for outbound internet.

DB Route Table:

Attached to DB Subnets.

No internet access.

# 6) Internet Gateway & NAT

![](/images/Screenshot%202025-09-17%20164755.png)

IGW: Provides internet access to Public subnet.

NAT Gateway: Allows private App instances to access internet for updates.

# 7) Security Groups

Web SG (sg-web)

Inbound: Allow 80/443 from 0.0.0.0/0, SSH from trusted IP.

Outbound: Open or restricted to App SG.

App SG (sg-app)

Inbound: Allow App port (e.g., 8080) only from Web SG.

Outbound: Allow DB traffic to DB SG.

DB SG (sg-db)

Inbound: Allow 3306/5432 only from App SG.

Outbound: Limited as required.

# 8) NACLs (Optional)

Stateless firewall at subnet level.

Default is allow-all, can be restricted for extra security.

# 9) EC2 Instances (2 Servers)

![](/images/Screenshot%202025-09-17%20164836.png)

Web Server (Public Subnet)

Role: Serves web requests, static/dynamic content.

Recommended: Amazon Linux 2 or Ubuntu.

Attach IAM Role for S3/Secrets access.

App Server (Private Subnet)

Role: Business logic, API layer.

No public IP.

Accessed only from Web server or via SSM.

Optional Bastion Host:

Use for SSH into private instances, placed in Public Subnet.

# 10) RDS (Database)

![](/images/Screenshot%202025-09-17%20164955.png)

Engine: MySQL or PostgreSQL.

Deployment: Multi-AZ enabled.

Storage: GP3, autoscaling enabled.

Subnet Group: DB Subnet A + DB Subnet B.

Public Access: Disabled.

Backups: Automated snapshots enabled.

Credentials: Store in Secrets Manager.

# 11) S3 Bucket

![](/images/Screenshot%202025-09-17%20164934.png)

Purpose: Static assets, backups, logs.

Enable encryption and versioning.

Block public access; serve via CloudFront.

# 12) CloudFront

![](/images/Screenshot%202025-09-17%20164909.png)

Global CDN for caching.

Origin: S3 or Web server.

Enforce HTTPS.

Use OAI/OAC for secure S3 origin access.

# 13) IAM & Access Keys

![](/images/Screenshot%202025-09-17%20165048.png)

Use IAM Roles instead of static keys.

For CI/CD, create least-privilege IAM users.

Rotate keys regularly.

Enable CloudTrail for auditing.

# 14) Traffic Flow

User → CloudFront.

CloudFront serves static from S3 or forwards to Web EC2.

Web EC2 forwards dynamic requests to App EC2.

App EC2 communicates with RDS.

Response flows back → Web → CloudFront → User.

# 15) Logging & Monitoring

CloudWatch: Metrics and alarms for EC2, RDS.

CloudTrail: Logs API calls.

S3 & CloudFront access logs enabled.

Optional: X-Ray for tracing.

# 16) Backups & Disaster Recovery

RDS automated backups and snapshots.

S3 versioning and cross-region replication.

Regular AMI snapshots for EC2.

# 17) Cost Considerations

NAT Gateway, RDS Multi-AZ, and CloudFront add extra cost.

Use S3 lifecycle rules to move old logs to Glacier.

# 18) Deployment Steps (High-Level)

Create VPC and subnets.

Attach Internet Gateway.

Configure Route Tables.

Create Security Groups.

Launch Web EC2 in Public Subnet.

Launch App EC2 in Private Subnet.

Create RDS in DB Subnet Group (Multi-AZ).

Create S3 bucket.

Create CloudFront distribution.

Point Route 53 DNS to CloudFront.

# 19) Example Naming Convention

VPC: three-tier-vpc

Subnets: web-subnet, app-subnet, db-subnet-a, db-subnet-b

EC2: web-server, app-server

RDS: main-db

S3: project-assets

CloudFront: project-cf

SGs: sg-web, sg-app, sg-db