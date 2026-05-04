terraform {
  required_providers {
    aws = {
        source = "hashicorp/aws"
        version = "~> 5.0"
    }
  }
}

provider "aws" {
    region = var.aws_region
}

resource "aws_security_group" "main" {
    name = "${var.project_name}-sg"

    ingress {
        from_port = 22
        to_port = 22
        protocol = "tcp"
        cidr_blocks = [ "0.0.0.0/0" ]
    }

    ingress {
        from_port = 80
        to_port = 80
        protocol = "tcp"
        cidr_blocks = [ "0.0.0.0/0" ]
    }

    ingress {
        from_port   = 443
        to_port     = 443
        protocol    = "tcp"
        cidr_blocks = ["0.0.0.0/0"]
    }

    egress {
        from_port = 0
        to_port = 0
        protocol = "-1"
        cidr_blocks = [ "0.0.0.0/0" ]
    }
}

resource "aws_key_pair" "main" {
  key_name   = "${var.project_name}-key"
  public_key = file(var.ssh_public_key_path)
} 

resource "aws_instance" "main" {
    ami = var.ami_id
    instance_type = var.instance_type
    key_name = aws_key_pair.main.key_name
    vpc_security_group_ids = [aws_security_group.main.id]

    root_block_device {
        volume_size = 20
        volume_type = "gp3"
    }

    tags = {
      Name = var.project_name
    }
}

# Generowanie pliku inventory pod Ansible
resource "local_file" "ansible_inventory" {
  content = <<-EOT
    [k8s]
    ${aws_instance.main.public_ip} ansible_user=ubuntu ansible_ssh_private_key_file=${var.ssh_private_key_path}
  EOT
  filename = "../ansible/inventory.ini"
}
