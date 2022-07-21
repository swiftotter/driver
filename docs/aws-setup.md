# AWS Setup

Driver's default implementation is to use AWS for the database transformation (RDS) and the storage of the transformed
databases (S3).

You will need to do two things in your AWS control panel:
1. Create a new policy.
2. Assign that policy to a new user.

## Policy Creation

Open your control panel and go to IAM. Click on the Policies tab on the sidebar. Choose to Create New Policy.
Select Create Your Own Policy (if you want to use the one below) and enter the following code.

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "ec2:AuthorizeSecurityGroupIngress",
                "ec2:CreateSecurityGroup",
                "s3:GetObject",
                "s3:PutObject",
                "rds:CreateDBInstance",
                "rds:DeleteDBInstance",
                "rds:DescribeDBInstances"
            ],
            "Resource": [
                "*"
            ]
        }
    ]
}
```

## User Creation

In the IAM control panel, click on the Users tab. Select Add user. Choose a username. This will only be seen by you
in the control panel. Check the Programmatic access as Driver will be needing a access key ID and a secret access key.
Select Add existing policies directly and choose your newly-created policy. Review it and then create the user.

Place the Access key ID and Secret access key in your configuration.
