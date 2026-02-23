# Cost Modeling & Resource Planning

**Purpose:** Infrastructure cost analysis and scaling projections  
**Audience:** System Admin, Finance, Leadership  
**Last Updated:** 2026-02-23

---

## Executive Summary

Current deployment supports 2,000 users on self-hosted infrastructure. This document models costs for current state, 10x growth scenario (20,000 users), and cloud migration options.

**Key Findings:**
- Current self-hosted: ~$200-400/month (server + storage)
- 10x growth self-hosted: ~$800-1,500/month (vertical scaling)
- Cloud migration (Azure/AWS): ~$1,200-2,500/month at 20,000 users
- Break-even point: ~5,000 users (cloud becomes cost-competitive)

---

## Current Infrastructure Costs

### Self-Hosted Deployment (2,000 Users)

**Server Specifications:**
- CPU: 4-8 cores
- RAM: 16-32 GB
- Storage: 200 GB SSD
- Bandwidth: 1 TB/month

**Monthly Costs:**

| Component | Specification | Monthly Cost |
|-----------|--------------|--------------|
| Server (VPS/Dedicated) | 8 cores, 32GB RAM | $150-300 |
| Storage | 200 GB SSD | $20-40 |
| Bandwidth | 1 TB | $10-20 |
| Backup Storage | 300 GB off-site | $10-20 |
| Domain/SSL | Annual cost amortized | $5-10 |
| **Total** | | **$195-390/month** |

**Annual Cost:** $2,340-4,680

**Cost per User:** $0.10-0.20/month

---

### Resource Utilization (Current)

**Database:**
- Size: 10 GB
- Growth rate: 200-400 MB/month (mostly log tables)
- Projected 12 months: 15 GB
- Note: Purge `logstore_standard_log` on a 90-day cycle to control growth

**File Storage:**
- Size: 50 GB (mostly PowerPoint, PDFs)
- Growth rate: 5 GB/month
- Projected 12 months: 110 GB

**Backup Storage:**
- Daily: 60 GB × 7 days = 420 GB
- Weekly: 70 GB × 4 weeks = 280 GB
- Monthly: 80 GB × 12 months = 960 GB
- Total: ~1.6 TB (with compression/deduplication: ~600 GB)

**Bandwidth:**
- Average: 500 GB/month
- Peak: 800 GB/month (enrollment periods)
- Projected 12 months: 600 GB/month average

---

## 10x Growth Scenario (20,000 Users)

### Scaling Strategy: Vertical First, Then Horizontal

**Phase 1: Vertical Scaling (5,000-10,000 users)**

**Server Upgrade:**
- CPU: 16 cores
- RAM: 64 GB
- Storage: 500 GB NVMe SSD
- Bandwidth: 3 TB/month

**Monthly Costs:**

| Component | Specification | Monthly Cost |
|-----------|--------------|--------------|
| Server | 16 cores, 64GB RAM | $400-600 |
| Storage | 500 GB NVMe SSD | $50-80 |
| Bandwidth | 3 TB | $30-50 |
| Backup Storage | 1.5 TB off-site | $30-50 |
| CDN (optional) | Cloudflare Pro | $20 |
| **Total** | | **$530-800/month** |

**Annual Cost:** $6,360-9,600  
**Cost per User:** $0.05-0.08/month

---

**Phase 2: Horizontal Scaling (10,000-20,000 users)**

**Architecture:**
- 2× Web servers (load balanced)
- 1× Database server (dedicated)
- 1× Redis cache server
- Shared file storage (NFS or S3)

**Monthly Costs:**

| Component | Specification | Monthly Cost |
|-----------|--------------|--------------|
| Web Server 1 | 8 cores, 32GB RAM | $200-300 |
| Web Server 2 | 8 cores, 32GB RAM | $200-300 |
| Database Server | 16 cores, 64GB RAM | $400-600 |
| Redis Cache | 4 cores, 16GB RAM | $100-150 |
| Load Balancer | Managed service | $50-100 |
| Storage (NFS/S3) | 1 TB | $50-100 |
| Bandwidth | 5 TB | $50-100 |
| Backup Storage | 3 TB off-site | $60-100 |
| CDN | Cloudflare Pro | $20 |
| **Total** | | **$1,130-1,870/month** |

**Annual Cost:** $13,560-22,440  
**Cost per User:** $0.06-0.09/month

---

### Resource Projections (20,000 Users)

**Database:**
- Size: 100 GB (10x current)
- Growth rate: 10 GB/month
- Query load: 10x current (requires optimization)

**File Storage:**
- Size: 500 GB (10x current)
- Growth rate: 50 GB/month
- CDN recommended for static assets

**Backup Storage:**
- Daily: 600 GB × 7 days = 4.2 TB
- Weekly: 700 GB × 4 weeks = 2.8 TB
- Monthly: 800 GB × 12 months = 9.6 TB
- Total: ~16 TB (with compression: ~6 TB)

**Bandwidth:**
- Average: 5 TB/month
- Peak: 8 TB/month (enrollment periods)
- CDN offload: 60-70% of static assets

---

## Cloud Migration Cost Analysis

### Azure Option

**Azure App Service + Azure Database for MySQL**

**Monthly Costs (2,000 users) — Standard Tier:**

| Component | Specification | Monthly Cost |
|-----------|--------------|--------------|
| App Service | P2v3 (2 cores, 8GB RAM) | $146 |
| Database | General Purpose 4 vCores | $290 |
| Storage | 200 GB Premium SSD | $30 |
| Bandwidth | 1 TB outbound | $87 |
| Backup | 300 GB GRS | $15 |
| Azure Key Vault | Secrets management | $3 |
| **Total** | | **$571/month** |

**Annual Cost:** $6,852  
**Cost per User:** $0.29/month

> **Note:** A burstable configuration (B2s App Service + Flexible Server Burstable) can reduce this to **~$60-80/month** for a prototyping or low-traffic deployment. The figures above assume production-grade SKUs.

---

**Monthly Costs (20,000 users):**

| Component | Specification | Monthly Cost |
|-----------|--------------|--------------|
| App Service | P3v3 (4 cores, 16GB RAM) × 2 | $584 |
| Database | General Purpose 16 vCores | $1,160 |
| Storage | 1 TB Premium SSD | $150 |
| Bandwidth | 5 TB outbound | $435 |
| Backup | 3 TB GRS | $150 |
| Azure CDN | Standard tier | $40 |
| Azure Key Vault | Secrets management | $3 |
| **Total** | | **$2,522/month** |

**Annual Cost:** $30,264  
**Cost per User:** $0.13/month

---

### AWS Option

**Elastic Beanstalk + RDS MySQL**

**Monthly Costs (2,000 users):**

| Component | Specification | Monthly Cost |
|-----------|--------------|--------------|
| EC2 (Elastic Beanstalk) | t3.large (2 cores, 8GB RAM) | $60 |
| RDS MySQL | db.t3.large (2 cores, 8GB RAM) | $122 |
| EBS Storage | 200 GB gp3 | $16 |
| S3 Storage | 100 GB | $2 |
| Data Transfer | 1 TB outbound | $90 |
| Backup (S3) | 300 GB | $7 |
| AWS Secrets Manager | 10 secrets | $4 |
| **Total** | | **$301/month** |

**Annual Cost:** $3,612  
**Cost per User:** $0.15/month

---

**Monthly Costs (20,000 users):**

| Component | Specification | Monthly Cost |
|-----------|--------------|--------------|
| EC2 (Elastic Beanstalk) | m5.xlarge (4 cores, 16GB RAM) × 2 | $280 |
| RDS MySQL | db.r5.2xlarge (8 cores, 64GB RAM) | $730 |
| EBS Storage | 1 TB gp3 | $80 |
| S3 Storage | 500 GB | $12 |
| Data Transfer | 5 TB outbound | $450 |
| Backup (S3 Glacier) | 3 TB | $12 |
| CloudFront CDN | 2 TB transfer | $170 |
| AWS Secrets Manager | 20 secrets | $8 |
| **Total** | | **$1,742/month** |

**Annual Cost:** $20,904  
**Cost per User:** $0.09/month

---

## Cost Comparison Summary

### 2,000 Users

| Option | Monthly Cost | Annual Cost | Cost/User/Month |
|--------|--------------|-------------|-----------------|
| Self-Hosted | $195-390 | $2,340-4,680 | $0.10-0.20 |
| Azure | $571 | $6,852 | $0.29 |
| AWS | $301 | $3,612 | $0.15 |

**Winner:** Self-hosted (50-60% cheaper)

---

### 20,000 Users

| Option | Monthly Cost | Annual Cost | Cost/User/Month |
|--------|--------------|-------------|-----------------|
| Self-Hosted (Horizontal) | $1,130-1,870 | $13,560-22,440 | $0.06-0.09 |
| Azure | $2,522 | $30,264 | $0.13 |
| AWS | $1,742 | $20,904 | $0.09 |

**Winner:** Self-hosted (35-40% cheaper) or AWS (competitive)

---

## Break-Even Analysis

**Cloud becomes cost-competitive at:**
- AWS: ~3,000-5,000 users (when admin overhead is factored in)
- Azure (standard SKUs): ~8,000 users (higher base costs, better enterprise features)
- Azure (burstable): Competitive immediately for low-traffic deployments

> **Important:** When factoring in self-hosted admin overhead (~$1,875/month at $75/hr, see Hidden Costs section), cloud is cost-competitive even at 2,000 users. The break-even figures above compare pure infrastructure costs only.

**Factors favoring cloud migration:**
- No in-house DevOps team (admin overhead)
- Need for high availability (99.9% SLA)
- Compliance requirements (SOC 2, HIPAA)
- Rapid scaling needs (unpredictable growth)
- Disaster recovery automation

**Factors favoring self-hosted:**
- Existing DevOps expertise
- Predictable growth
- Cost sensitivity
- Data sovereignty requirements

---

## Plugin License Costs

### Current Plugins (All Open Source)

**Core Plugins:**
- Attendance: Free (GPL)
- Scheduler: Free (GPL)
- Competency Framework: Core Moodle (Free)
- Block XP: Free (GPL)
- Block Stash: Free (GPL)

**Custom Plugins:**
- local_sceh_rules: Internal development
- block_sceh_dashboard: Internal development
- local_kirkpatrick_level4: Internal development
- local_sceh_importer: Internal development

**Total Plugin Costs:** $0/month

---

### Commercial Plugin Considerations

**If migrating to commercial alternatives:**

| Plugin | Commercial Alternative | Cost |
|--------|------------------------|------|
| Block XP | Level Up XP Pro | $99/year |
| Attendance | Attendance Pro | $149/year |
| Scheduler | Booking Manager | $199/year |
| **Total** | | **$447/year** |

**Recommendation:** Stick with open-source plugins. Commercial alternatives offer minimal additional value for current use case.

---

## Hidden Costs & Considerations

### Self-Hosted Hidden Costs

**DevOps/Admin Time:**
- System maintenance: 10 hours/month
- Security updates: 5 hours/month
- Backup verification: 2 hours/month
- Monitoring/troubleshooting: 8 hours/month
- **Total:** 25 hours/month

**At $75/hour:** $1,875/month (often overlooked)

**Total Cost of Ownership (2,000 users):**
- Infrastructure: $195-390/month
- Admin time: $1,875/month
- **Total:** $2,070-2,265/month

**At this scale, cloud becomes competitive when factoring in admin overhead.**

---

### Cloud Hidden Costs

**Data Transfer:**
- Egress charges (outbound bandwidth)
- Inter-region transfer
- Backup retrieval costs

**Support:**
- Basic support: Included
- Developer support: $29-100/month
- Business support: $100-500/month

**Vendor Lock-In:**
- Migration costs if switching providers
- Proprietary service dependencies

---

## Scaling Cost Projections (5-Year)

| Year | Users | Self-Hosted | AWS | Azure |
|------|-------|-------------|-----|-------|
| 1 | 2,000 | $2,340 | $3,612 | $6,852 |
| 2 | 5,000 | $6,360 | $9,000 | $15,000 |
| 3 | 10,000 | $9,600 | $14,400 | $21,600 |
| 4 | 15,000 | $13,560 | $18,000 | $27,000 |
| 5 | 20,000 | $22,440 | $20,904 | $30,264 |
| **Total** | | **$54,300** | **$65,916** | **$100,716** |

**5-Year Savings (Self-Hosted vs AWS):** $11,616 (18%)  
**5-Year Savings (Self-Hosted vs Azure):** $46,416 (46%)

---

## Recommendations

### Current State (2,000 Users)
✅ **Continue self-hosted deployment**
- Most cost-effective
- Adequate performance
- Manageable admin overhead

**Action Items:**
1. Implement cost monitoring (track actual vs. projected)
2. Optimize database queries (reduce compute needs)
3. Enable CDN for static assets (reduce bandwidth costs)
4. Automate backup verification (reduce admin time)

---

### Growth to 5,000 Users
✅ **Vertical scaling (upgrade server)**
- Still cost-effective
- Simpler than horizontal scaling
- Defer cloud migration decision

**Action Items:**
1. Upgrade to 16 cores, 64GB RAM
2. Migrate to NVMe SSD storage
3. Implement Redis caching
4. Add performance monitoring

---

### Growth to 10,000+ Users
⚠️ **Evaluate cloud migration**
- AWS becomes cost-competitive
- Reduced admin overhead
- Better disaster recovery
- Easier horizontal scaling

**Decision Criteria:**
- If admin time >40 hours/month → Migrate to cloud
- If growth rate >50% year-over-year → Migrate to cloud
- If compliance requirements increase → Migrate to cloud
- Otherwise → Continue self-hosted with horizontal scaling

---

### Growth to 20,000 Users
✅ **Hybrid approach or full cloud**
- Self-hosted horizontal scaling: Most cost-effective
- AWS: Competitive, better managed services
- Azure: Higher cost, better enterprise integration

**Recommendation:** AWS if migrating to cloud, self-hosted if DevOps expertise available.

---

## Cost Optimization Strategies

### Immediate (0-3 Months)

1. **Enable Moodle Caching**
   - Impact: 30-50% performance improvement
   - Cost: $0 (built-in feature)
   - Time: 2 hours setup

2. **Optimize Database Queries**
   - Impact: 10-20% compute reduction
   - Cost: $0 (optimization work)
   - Time: 8 hours analysis + fixes

3. **External Video Hosting**
   - Impact: 80% storage reduction
   - Cost: $0 (YouTube/Vimeo free tiers)
   - Time: 4 hours migration

4. **Compress Backups**
   - Impact: 60% backup storage reduction
   - Cost: $0 (gzip/tar built-in)
   - Time: 2 hours script update

**Total Savings:** $50-100/month

---

### Short-Term (3-6 Months)

1. **Implement CDN (Cloudflare Free)**
   - Impact: 40-60% bandwidth reduction
   - Cost: $0 (free tier)
   - Time: 4 hours setup

2. **Redis Caching**
   - Impact: 30% database load reduction
   - Cost: $20/month (small Redis instance)
   - Time: 6 hours setup

3. **Automated Cleanup Tasks**
   - Impact: 20% storage reduction
   - Cost: $0 (scheduled tasks)
   - Time: 4 hours scripting

**Total Savings:** $30-60/month (net: $10-40/month after Redis cost)

---

### Long-Term (6-12 Months)

1. **Migrate to Object Storage (S3/Azure Blob)**
   - Impact: 50% file storage cost reduction
   - Cost: Variable (pay-per-use)
   - Time: 16 hours migration

2. **Implement Auto-Scaling (Cloud Only)**
   - Impact: 20-30% compute cost reduction
   - Cost: Cloud migration required
   - Time: 40 hours migration

3. **Database Read Replicas (Cloud Only)**
   - Impact: 40% database load distribution
   - Cost: +50% database cost, but enables scaling
   - Time: 8 hours setup

---

## Budget Recommendations

### Annual IT Budget (2,000 Users)

| Category | Annual Cost | Notes |
|----------|-------------|-------|
| Infrastructure | $2,340-4,680 | Server, storage, bandwidth |
| Backup Storage | $360-600 | Off-site backups |
| Security (SSL, monitoring) | $240-480 | Certificates, tools |
| Contingency (20%) | $588-1,152 | Unexpected costs |
| **Total** | **$3,528-6,912** | |

**Recommended Budget:** $7,000/year (includes buffer)

---

### Annual IT Budget (20,000 Users)

| Category | Annual Cost | Notes |
|----------|-------------|-------|
| Infrastructure | $13,560-22,440 | Horizontal scaling |
| Backup Storage | $720-1,200 | 3 TB off-site |
| Security | $600-1,200 | Enhanced monitoring |
| CDN | $240-480 | Cloudflare Pro |
| Contingency (20%) | $3,024-5,064 | Unexpected costs |
| **Total** | **$18,144-30,384** | |

**Recommended Budget:** $30,000/year (self-hosted) or $25,000/year (AWS)

---

## Monitoring & Alerts

### Cost Monitoring Dashboard

**Track Monthly:**
- Server costs (actual vs. budget)
- Storage growth rate
- Bandwidth usage
- Backup storage size
- Admin time spent

**Alert Thresholds:**
- Storage >80% capacity → Upgrade needed
- Bandwidth >80% limit → CDN or upgrade needed
- Database >80% capacity → Optimization or upgrade needed
- Admin time >40 hours/month → Consider cloud migration

---

## Next Steps

### Immediate Actions
1. ✅ Document current infrastructure costs (baseline)
2. ✅ Implement cost tracking dashboard
3. ✅ Enable Moodle caching (quick win)
4. ✅ Set up cost alerts (storage, bandwidth)

### Quarterly Reviews
1. Review actual vs. projected costs
2. Assess growth rate (users, storage, bandwidth)
3. Evaluate cloud migration if thresholds met
4. Update 5-year projections

### Annual Planning
1. Budget for next year based on growth projections
2. Evaluate new cloud pricing (prices drop ~10% annually)
3. Assess plugin license needs
4. Plan infrastructure upgrades

---

**Document Version:** 1.0  
**Last Updated:** 2026-02-23  
**Next Review:** 2026-05-23 (Quarterly)
